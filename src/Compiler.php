<?php
namespace Mill;

use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Annotations\ErrorAnnotation;
use Mill\Parser\Annotations\PathAnnotation;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Annotations\VendorTagAnnotation;
use Mill\Parser\Representation;
use Mill\Parser\Resource;
use Mill\Parser\Version;

class Compiler
{
    /** @var Application */
    protected $application;

    /** @var Config */
    protected $config;

    /** @var Version|null */
    protected $version = null;

    /** @var array */
    protected $supported_versions = [];

    /** @var array */
    protected $compiled_resources = [];

    /** @var array */
    protected $compiled_representations = [];

    /** @var array */
    protected $parsed_resources = [];

    /** @var array */
    protected $parsed_representations = [];

    /**
     * A setting to compile documentation for documentation that's been marked, through a `:private` decorator, as
     * private.
     *
     * @var bool
     */
    protected $load_private_docs = true;

    /**
     * Vendor tags to compile documentation against. If this array contains vendor tags, only public and documentation
     * that have a matched tag will be compiled. If this is null, this will be disregarded and everything will be
     * compiled.
     *
     * @var array|null
     */
    protected $load_vendor_tag_docs = null;

    /**
     * @param Application $application
     * @param Version|null $version
     */
    public function __construct(Application $application, Version $version = null)
    {
        $this->application = $application;
        $this->config = $application->getConfig();
        $this->version = $version;

        $this->supported_versions = $this->config->getApiVersions();
    }

    public function compile(): void
    {
        $controllers = $this->config->getControllers();
        foreach ($controllers as $controller) {
            $docs = new Resource\Documentation($controller, $this->application);

            /** @var Resource\Action\Documentation $resource */
            foreach ($docs->getMethods() as $resource) {
                $this->compileResourceAction($resource);
            }
        }

        foreach ($this->compiled_resources as $version => $groups) {
            ksort($this->compiled_resources[$version]);
        }

        foreach ($this->compiled_representations as $version => $data) {
            ksort($this->compiled_representations[$version]);
        }
    }

    /**
     * Compile a resource action.
     *
     * @param Resource\Action\Documentation $resource
     * @throws \Exception
     */
    protected function compileResourceAction(Resource\Action\Documentation $resource): void
    {
        $group = $resource->getGroup();

        // Set the amount of aliases that we've accrued here so we can properly enforce uniqueness of operation
        // IDs on aliased paths.
        $aliases = 0;

        /** @var PathAnnotation $path */
        foreach ($resource->getPaths() as $path) {
            // Are we compiling documentation for a private or protected resource?
            if (!$this->shouldParsePath($resource, $path)) {
                continue;
            }

            // Set any params that belong to this path on onto this action.
            $params = [];

            /** @var \Mill\Parser\Annotations\PathParamAnnotation $param */
            foreach ($resource->getPathParameters() as $param) {
                if ($path->doesPathHaveParam($param)) {
                    $params[$param->getField()] = $param;
                }
            }

            // Set the lone path that this action and group run under.
            $action = clone $resource;
            $action->setPath($path);
            $action->setPathParams($params);
            $action->filterAnnotationsForVisibility($this->load_private_docs, $this->load_vendor_tag_docs);

            if ($path->isAliased()) {
                $action->incrementOperationId(++$aliases);
            }

            // Hash the action so we don't happen to double up and end up with dupes, and then remove the
            // currently non-hash index from the action array.
            $identifier = $action->getPath()->getPath() . '::' . $action->getMethod();

            // Store the parsed, but not versioned, action so it can be used during changelog generation.
            if (!isset($this->parsed_resources[$group])) {
                $this->parsed_resources[$group] = [
                    'actions' => []
                ];
            }

            $this->parsed_resources[$group]['actions'][$identifier] = $action;

            // Run through every supported API version.
            foreach ($this->supported_versions as $supported_version) {
                $version = $supported_version['version'];

                // If we're compiling documentation for a specific version range, and this doesn't fall in that,
                // then skip it.
                if ($this->version && !$this->version->matches($version)) {
                    continue;
                }

                // If this method has either a minimum or maximum version specified, and we aren't compiling an
                // acceptable version, skip it.
                if (!$action->fallsWithinVersion($version)) {
                    continue;
                }

                if (!isset($this->compiled_resources[$version])) {
                    $this->compiled_resources[$version] = [];
                } elseif (!isset($this->compiled_resources[$version][$group])) {
                    $this->compiled_resources[$version][$group] = [
                        'actions' => []
                    ];
                }

                // Filter down the annotations on this action for just those of the current version we're
                // compiling documentation for.
                $cloned = clone $action;
                $cloned->filterAnnotationsForVersion($version);

                // Compile any representations
                $responses = $cloned->getResponses();
                if (!empty($responses)) {
                    /** @var ReturnAnnotation|ErrorAnnotation $response */
                    foreach ($responses as $response) {
                        $representation = $response->getRepresentation();
                        if (!empty($representation)) {
                            $this->compileRepresentation($version, $representation);
                        }
                    }
                }

                $this->compiled_resources[$version][$group]['actions'][$identifier] = $cloned;

                $this->transposeAction($version, $group, $identifier, $cloned);
            }
        }
    }

    /**
     * Compile a representation for a supplied API version.
     *
     * @param string $version
     * @param string $representation
     */
    protected function compileRepresentation(string $version, string $representation): void
    {
        $representations = $this->config->getAllRepresentations();

        // We don't need to worry about returning errors here if the supplied representation doesn't exist because
        // we've already handled that within the Annotation class(es).
        if (!isset($representations[$representation])) {
            return;
        }

        $representation = $representations[$representation];

        $class = $representation['class'];
        $method = $representation['method'];

        // If this representation has already been compiled for the supplied version, don't compile it again.
        if (isset($this->compiled_representations[$version][$class])) {
            return;
        }

        // If the representation is being excluded, then don't set it up for compilation.
        if ($this->config->isRepresentationExcluded($class)) {
            return;
        }

        $parsed = (new Representation\Documentation($class, $method, $this->application))->parse();
        $parsed->filterAnnotationsForVisibility($this->load_vendor_tag_docs);

        $this->parsed_representations[$class] = clone $parsed;

        $parsed->filterRepresentationForVersion($version);

        $this->compiled_representations[$version][$class] = $parsed;

        // Run through this representation to see if there are any linked representations that we should also compile.
        /** @var DataAnnotation $annotation */
        foreach ($parsed->getRawContent() as $annotation) {
            $this->compileRepresentation($version, $annotation->getType());

            $subtype = $annotation->getSubtype();
            if (!empty($subtype)) {
                $this->compileRepresentation($version, $subtype);
            }
        }

        $this->transposeRepresentation($version, $parsed);
    }

    /**
     * Event-like handler for transposing a compiled action into another format (like an OpenAPI schema).
     *
     * @param string $version
     * @param string $group
     * @param string $identifier
     * @param Resource\Action\Documentation $action
     */
    protected function transposeAction(
        string $version,
        string $group,
        string $identifier,
        Resource\Action\Documentation $action
    ): void {
        return;
    }

    /**
     * Event-like handler for transposing a compiled representation into another format (like an OpenAPI schema).
     *
     * @param string $version
     * @param Representation\Documentation $representation
     */
    protected function transposeRepresentation(string $version, Representation\Documentation $representation): void
    {
        return;
    }

    /**
     * Get compiled representations.
     *
     * @param null|string|Version $version
     * @return array
     */
    public function getRepresentations($version = null): array
    {
        if (empty($version)) {
            return $this->compiled_representations;
        }

        if ($version instanceof Version) {
            $version = $version->getConstraint();
        }

        return $this->compiled_representations[$version];
    }

    /**
     * Pull a representation from the current versioned set of representations.
     *
     * @param string $representation
     * @param string $version
     * @return false|\Mill\Parser\Representation\Documentation
     */
    protected function getRepresentation(string $representation, string $version)
    {
        $representations = $this->compiled_representations[$version];

        return (isset($representations[$representation])) ? $representations[$representation] : false;
    }

    /**
     * Get compiled resources.
     *
     * @param null|string $version
     * @return array
     */
    public function getResources(string $version = null): array
    {
        if (empty($version)) {
            return $this->compiled_resources;
        }

        return $this->compiled_resources[$version];
    }

    /**
     * Set if we'll be loading documentation that's been marked as being private.
     *
     * @param bool $load_private_docs
     * @return Compiler
     */
    public function setLoadPrivateDocs(bool $load_private_docs = true): self
    {
        $this->load_private_docs = $load_private_docs;
        return $this;
    }

    /**
     * Set an array of vendor tags that we'll be compiling documentation against.
     *
     * If you want all documentation, even that which has a vendor tag, supply `null`. If you want documentation that
     * either has no vendor tag, or specific ones, supply an array with those vendor tag names.
     *
     * @param array|null $vendor_tags
     * @return Compiler
     */
    public function setLoadVendorTagDocs(?array $vendor_tags): self
    {
        $this->load_vendor_tag_docs = $vendor_tags;
        return $this;
    }

    /**
     * With the rules set up on the compiler, should we parse a supplied path?
     *
     * @param Resource\Action\Documentation $method
     * @param PathAnnotation $path
     * @return bool
     */
    private function shouldParsePath(Resource\Action\Documentation $method, PathAnnotation $path): bool
    {
        $path_data = $path->toArray();
        $vendor_tags = $method->getVendorTags();

        // Should we compile documentation that has a vendor tag?
        if (!empty($vendor_tags) && !is_null($this->load_vendor_tag_docs)) {
            // We don't have any configured vendor tags to pull documentation for, so this path shouldn't be parsed.
            if (empty($this->load_vendor_tag_docs)) {
                return false;
            }

            $all_found = true;

            /** @var VendorTagAnnotation $vendor_tag */
            foreach ($vendor_tags as $vendor_tag) {
                if (!in_array($vendor_tag->getVendorTag(), $this->load_vendor_tag_docs)) {
                    $all_found = false;
                }
            }

            // This path should only be parsed if it has every vendor tag we're looking for.
            if ($all_found) {
                return true;
            }

            return false;
        }

        // If we aren't compiling docs for private resource, but this path is private, we shouldn't parse it.
        if (!$this->load_private_docs && !$path_data['visible']) {
            return false;
        }

        return true;
    }
}
