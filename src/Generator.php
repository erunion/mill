<?php
namespace Mill;

use Mill\Parser\Annotations\CapabilityAnnotation;
use Mill\Parser\Annotations\UriAnnotation;
use Mill\Parser\Representation;
use Mill\Parser\Resource;
use Mill\Parser\Version;

/**
 * Generator class for generating (compiling) API documentation down into a parseable array. This class can be then be
 * extended to further generate other formats (API Blueprint, OAI, Swagger, etc.)
 *
 */
class Generator
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Version|null
     */
    protected $version = null;

    /**
     * @var array
     */
    protected $supported_versions = [];

    /**
     * Compiled documentation.
     *
     * @var array
     */
    protected $compiled = [
        'representations' => [],
        'resources' => []
    ];

    /**
     * Setting to generate documentation for documentation that's been marked, through a `:private` decorator, as
     * private.
     *
     * @var bool
     */
    protected $load_private_docs = true;

    /**
     * Capabilities to generate documentation against. If this array contains capabilities, only public and
     * documentation that have that capability will be generated. If this is empty, this will be disregarded, and
     * everything will be generated.
     *
     * @var array
     */
    protected $load_capability_docs = [];

    /**
     * @param Config $config
     * @param Version|null $version
     */
    public function __construct(Config $config, Version $version = null)
    {
        $this->config = $config;
        $this->version = $version;

        $this->supported_versions = $this->config->getApiVersions();
    }

    /**
     * Compile API documentation into a parseable collection.
     *
     * @return array
     */
    public function generate()
    {
        // Generate resources.
        $resources = $this->compileResources($this->parseResources());
        foreach ($resources as $version => $groups) {
            // Alphabetize the versioned resource groups!
            ksort($resources[$version]);
        }

        $this->compiled['resources'] = $resources;

        // Generate representations.
        $representations = $this->compileRepresentations($this->parseRepresentations());
        foreach ($representations as $version => $data) {
            // Alphabetize the versioned representations!
            ksort($representations[$version]);
        }

        $this->compiled['representations'] = $representations;

        return $this->compiled;
    }

    /**
     * Run through configured controllers, parse them, and generate a collection of resource action documentation.
     *
     * @return array
     */
    protected function parseResources()
    {
        $resources = [];
        foreach ($this->config->getControllers() as $controller) {
            $docs = (new Resource\Documentation($controller))->parse();
            $annotations = $docs->toArray();

            /** @var \Mill\Parser\Resource\Action\Documentation $method */
            foreach ($docs->getMethods() as $method) {
                /** @var \Mill\Parser\Annotations\UriAnnotation $uri */
                foreach ($method->getUris() as $uri) {
                    $uri_data = $uri->toArray();
                    $group = $uri_data['group'];

                    // Are we generating documentation for a private or protected resource?
                    if (!$this->shouldParseUri($method, $uri)) {
                        continue;
                    }

                    $resource_label = $annotations['label'];
                    if (!isset($resources[$group]['resources'][$resource_label])) {
                        $resources[$group]['resources'][$resource_label] = [
                            'label' => $annotations['label'],
                            'description' => $annotations['description'],
                            'actions' => []
                        ];
                    }

                    // Set any segments that belong to this URI on onto this action.
                    $segments = [];

                    /** @var \Mill\Parser\Annotations\UriSegmentAnnotation $segment */
                    foreach ($method->getUriSegments() as $segment) {
                        if ($segment->getUri() === $uri_data['path']) {
                            $segments[] = $segment;
                        }
                    }

                    // Set the lone URI that this action and group run under.
                    $action = clone $method;
                    $action->setUri($uri);
                    $action->setUriSegments($segments);

                    // Hash the action so we don't happen to double up and end up with dupes.
                    $identifier = $action->getUri()->getPath() . '::' . $action->getMethod();

                    $resources[$group]['resources'][$resource_label]['actions'][$identifier] = $action;
                }
            }
        }

        return $resources;
    }

    /**
     * Compile parsed resources into a versioned collection.
     *
     * @param array $parsed
     * @return array
     */
    private function compileResources(array $parsed = [])
    {
        $resources = [];
        foreach ($parsed as $group => $group_data) {
            foreach ($group_data['resources'] as $resource_label => $resource) {
                /** @var Resource\Action\Documentation $action */
                foreach ($resource['actions'] as $identifier => $action) {
                    // Run through every supported API version and flatten out documentation for it.
                    foreach ($this->supported_versions as $version) {
                        // If we're generating documentation for a specific version range, and this doesn't fall in
                        // that, then skip it.
                        if ($this->version && !$this->version->matches($version)) {
                            continue;
                        }

                        // If this method has a minimum version specified, and we aren't generating for that, skip it.
                        $min_version = $action->getMinimumVersion();
                        if ($min_version && $min_version->getMinimumVersion() > $version) {
                            continue;
                        }

                        if (!isset($resources[$version])) {
                            $resources[$version] = [];
                        } elseif (!isset($resources[$version][$group])) {
                            $resources[$version][$group] = [
                                'resources' => []
                            ];
                        }

                        // Filter down the annotations on this action for just those of the current version we're
                        // generating documentation for.
                        $cloned = clone $action;
                        $cloned->filterAnnotationsForVersion($version);
                        $cloned->filterAnnotationsForVisibility($this->load_private_docs, $this->load_capability_docs);

                        if (!isset($resources[$version][$group]['resources'][$resource_label])) {
                            $resources[$version][$group]['resources'][$resource_label] = [
                                'label' => $resource['label'],
                                'description' => $resource['description'],
                                'actions' => []
                            ];
                        }

                        // Hash the action so we don't happen to double up and end up with dupes, and then remove the
                        // currently non-hash index from the action array.
                        $identifier = $cloned->getUri()->getPath() . '::' . $cloned->getMethod();

                        $resources[$version][$group]['resources'][$resource_label]['actions'][$identifier] = $cloned;
                    }
                }
            }
        }

        return $resources;
    }

    /**
     * Run through configured representations, parse them, and generate a collection of representation documentation.
     *
     * @return array
     */
    protected function parseRepresentations()
    {
        $representations = [];
        $error_representations = $this->config->getErrorRepresentations();

        /** @var array $representation */
        foreach ($this->config->getAllRepresentations() as $class => $representation) {
            // If we're running through a standard (non-error) representation, let's make sure we don't want it
            // excluded.
            if (!isset($error_representations[$class])) {
                // If the representation is being excluded, then don't set it up for compilation.
                if ($this->config->isRepresentationExcluded($class)) {
                    continue;
                }
            }

            $parsed = (new Representation\Documentation($class, $representation['method']))->parse();
            $representations[$class] = $parsed;
        }

        return $representations;
    }

    /**
     * Compile parsed representations into a versioned collection.
     *
     * @param array $parsed
     * @return array
     */
    private function compileRepresentations(array $parsed = [])
    {
        $representations = [];

        foreach ($parsed as $identifier => $representation) {
            // Run through every supported API version and flatten out documentation for it.
            foreach ($this->supported_versions as $version) {
                // If we're generating documentation for a specific version range, and this doesn't fall in
                // that, then skip it.
                if ($this->version && !$this->version->matches($version)) {
                    continue;
                }

                // Filter down the annotations on this action for just those of the current version we're
                // generating documentation for.
                $cloned = clone $representation;
                $cloned->filterRepresentationForVersion($version);

                $representations[$version][$identifier] = $cloned;
            }
        }

        return $representations;
    }

    /**
     * Get compiled representations.
     *
     * @param Version|string|null $version
     * @return array
     */
    public function getRepresentations($version = null)
    {
        if (empty($version)) {
            return $this->compiled['representations'];
        }

        if ($version instanceof Version) {
            $version = $version->getConstraint();
        }

        return $this->compiled['representations'][$version];
    }

    /**
     * Get compiled resources.
     *
     * @param string|null $version
     * @return array
     */
    public function getResources($version = null)
    {
        if (empty($version)) {
            return $this->compiled['resources'];
        }

        return $this->compiled['resources'][$version];
    }

    /**
     * Set if we'll be loading documentation that's been marked as being private.
     *
     * @param bool $load_private_docs
     * @return $this
     */
    public function setLoadPrivateDocs($load_private_docs = true)
    {
        $this->load_private_docs = $load_private_docs;
        return $this;
    }

    /**
     * Set an array of capabilities that we'll be generating documentation against.
     *
     * @param array $capabilities
     * @return $this
     */
    public function setLoadCapabilityDocs(array $capabilities = [])
    {
        $this->load_capability_docs = $capabilities;
        return $this;
    }

    /**
     * With the rules set up on the Generator, should we parse a supplied URI?
     *
     * @param Resource\Action\Documentation $method
     * @param UriAnnotation $uri
     * @return bool
     */
    private function shouldParseUri(Resource\Action\Documentation $method, UriAnnotation $uri)
    {
        $uri_data = $uri->toArray();
        $capabilities = $method->getCapabilities();

        // Should we generate documentation that is locked behind a capability?
        if (!empty($capabilities) && !empty($this->load_capability_docs)) {
            $all_found = true;

            /** @var CapabilityAnnotation $capability */
            foreach ($capabilities as $capability) {
                if (!in_array($capability->getCapability(), $this->load_capability_docs)) {
                    $all_found = false;
                }
            }

            // This URI should only be parsed if it has every capability we're looking for.
            if ($all_found) {
                return true;
            }

            return false;
        }

        // If we aren't generating docs for private resource, but this URI is private, we shouldn't parse it.
        if (!$this->load_private_docs && !$uri_data['visible']) {
            return false;
        }

        return true;
    }
}
