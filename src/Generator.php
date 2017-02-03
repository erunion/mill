<?php
namespace Mill;

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
     * @param Config $config
     * @param Version|null $version
     */
    public function __construct(Config $config, Version $version = null)
    {
        $this->config = $config;
        $this->version = $version;
    }

    /**
     * Compile API documentation into a parseable array.
     *
     * @return array
     */
    public function generate()
    {
        $this->compileResources();
        $this->compileRepresentations();

        return $this->compiled;
    }

    /**
     * Compile API resources.
     *
     * @return void
     */
    private function compileResources()
    {
        $since_version = $this->config->getSinceApiVersion();

        // Run through configured controllers and calculate the supported API versions across their application.
        $controllers = [];
        foreach ($this->config->getControllers() as $controller) {
            $docs = (new Resource\Documentation($controller))->parse();
            $methods = $docs->getMethods();

            /** @var \Mill\Parser\Resource\Action\Documentation $method */
            foreach ($methods as $method) {
                $this->supported_versions = array_merge(
                    $this->supported_versions,
                    $method->getSupportedVersions($since_version)
                );
            }

            $controllers[] = $docs;
        }

        $this->supported_versions = array_unique($this->supported_versions);
        sort($this->supported_versions);

        // Run through parsed controllers, and now generate a versioned array of parseable resource action
        // documentation.
        $resources = [];
        foreach ($controllers as $controller) {
            $annotations = $controller->toArray();

            /** @var \Mill\Parser\Resource\Action\Documentation $method */
            foreach ($controller->getMethods() as $method) {
                /** @var \Mill\Parser\Annotations\UriAnnotation $uri */
                foreach ($method->getUris() as $uri) {
                    $uri_data = $uri->toArray();

                    $group = $uri_data['group'];
                    $resource_label = $annotations['label'];

                    // Set any segments that belong to this URI on onto this action.
                    $segments = [];

                    /** @var \Mill\Parser\Annotations\UriSegmentAnnotation $segment */
                    foreach ($method->getUriSegments() as $segment) {
                        if ($segment->getUri() === $uri_data['path']) {
                            $segments[] = $segment;
                        }
                    }

                    foreach ($this->supported_versions as $version) {
                        // If we're generating documentation for a specific version range, and this doesn't fall in
                        // that, then skip it.
                        if ($this->version && !$this->version->matches($version)) {
                            continue;
                        }

                        // If this method has a minimum version specified, and we aren't generating for that, skip it.
                        $min_version = $method->getMinimumVersion();
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

                        // Set the lone URI that this action and group run under.
                        $action = clone $method;
                        $action->setUri($uri);
                        $action->setUriSegments($segments);

                        // Filter down the annotations on this action for just those of the current version we're
                        // generating documentation for.
                        $action->filterAnnotationsForVersion($version);

                        if (!isset($resources[$version][$group]['resources'][$resource_label])) {
                            $resources[$version][$group]['resources'][$resource_label] = [
                                'label' => $annotations['label'],
                                'description' => $annotations['description'],
                                'actions' => []
                            ];
                        }

                        // Hash the action so we don't happen to double up and end up with dupes, and then remove the
                        // currently non-hash index from the action array.
                        $index = $action->getUri()->getPath() . '::' . $action->getMethod();

                        $resources[$version][$group]['resources'][$resource_label]['actions'][$index] = $action;
                    }
                }
            }
        }

        // Alphabetize the versioned resource groups!
        foreach ($resources as $version => $groups) {
            uksort($resources[$version], function ($a, $b) {
                return ($a > $b) ? 1 : -1;
            });
        }

        $this->compiled['resources'] = $resources;
    }

    /**
     * Compile API representations.
     *
     * @return void
     */
    private function compileRepresentations()
    {
        $representations = [];

        /** @var array $representation */
        foreach ($this->config->getRepresentations() as $representation) {
            $class = $representation['class'];

            // If the representation is being ignored, then don't set it up for compilation.
            if ($this->config->isRepresentationIgnored($class)) {
                continue;
            }

            $docs = null;
            if (!isset($representation['no_method'])) {
                $docs = (new Representation\Documentation($class, $representation['method']))->parse();
            }

            foreach ($this->supported_versions as $version) {
                // If we're generating documentation for a specific version range, and this doesn't fall in
                // that, then skip it.
                if ($this->version && !$this->version->matches($version)) {
                    continue;
                }

                // If this method has been configured as having no method, then it doesn't have any annotations to
                // parse.
                if (isset($representation['no_method'])) {
                    $representations[$version][$class] = $docs;
                    continue;
                }

                // Filter down the annotations on this action for just those of the current version we're
                // generating documentation for.
                $cloned = clone $docs;
                $cloned->filterRepresentationForVersion($version);

                $representations[$version][$class] = $cloned;
            }
        }

        // Alphabetize the versioned representations!
        foreach ($representations as $version => $data) {
            uksort($representations[$version], function ($a, $b) {
                return ($a > $b) ? 1 : -1;
            });
        }

        $this->compiled['representations'] = $representations;
    }

    /**
     * Get compiled representations.
     *
     * @param string|null $version
     * @return array
     */
    public function getRepresentations($version = null)
    {
        if (empty($version)) {
            return $this->compiled['representations'];
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
}
