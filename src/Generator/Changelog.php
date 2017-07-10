<?php
namespace Mill\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Mill\Generator;
use Mill\Generator\Changelog\Json;
use Mill\Generator\Changelog\Markdown;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\ContentTypeAnnotation;
use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Representation\Documentation;
use Mill\Parser\Resource\Action;

class Changelog extends Generator
{
    const CHANGE_ACTION = 'action';
    const CHANGE_ACTION_PARAM = 'action_param';
    const CHANGE_ACTION_RETURN = 'action_return';
    const CHANGE_ACTION_THROWS = 'action_throws';
    const CHANGE_CONTENT_TYPE = 'content_type';
    const CHANGE_REPRESENTATION_DATA = 'representation_data';

    /**
     * Generated changelog.
     *
     * @var array
     */
    protected $changelog = [];

    /**
     * Parsed documentation.
     *
     * @var array
     */
    protected $parsed = [
        'representations' => [],
        'resources' => []
    ];

    /**
     * Take compiled API documentation and generate a changelog over the life of the API.
     *
     * @return array
     */
    public function generate()
    {
        $this->parsed['representations'] = $this->parseRepresentations();
        $this->parsed['resources'] = $this->parseResources();

        $this->buildRepresentationChangelog($this->parsed['representations']);
        $this->buildResourceChangelog($this->parsed['resources']);

        // Keep things tidy
        krsort($this->changelog);
        foreach ($this->changelog as $version => $changes) {
            $version_data = $this->config->getApiVersion($version);
            $this->changelog[$version]['_details'] = [
                'release_date' => $version_data['release_date']
            ];

            if (!empty($version_data['description'])) {
                $this->changelog[$version]['_details']['description'] = $version_data['description'];
            }

            ksort($this->changelog[$version]);
        }

        return $this->changelog;
    }

    /**
     * Take compiled API documentation and generate a JSON-encoded changelog over the life of the API.
     *
     * @return string
     */
    public function generateJson()
    {
        $json = new Json($this->config);
        $json->setChangelog($this->generate());
        return $json->generate();
    }

    /**
     * Take compiled API documentation and generate a Markdown-based changelog over the life of the API.
     *
     * @return string
     */
    public function generateMarkdown()
    {
        $markdown = new Markdown($this->config);
        $markdown->setChangelog($this->generate());
        return $markdown->generate();
    }

    /**
     * Compile a changelog for a parsed set of representations.
     *
     * @param array $representations
     * @return void
     */
    private function buildRepresentationChangelog(array $representations = [])
    {
        /** @var Documentation $representation */
        foreach ($representations as $representation) {
            $content = $representation->getRawContent();

            /** @var Annotation $annotation */
            foreach ($content as $identifier => $annotation) {
                $introduced = $this->getVersionIntroduced($annotation);
                if ($introduced) {
                    $this->logAdded($introduced, self::CHANGE_REPRESENTATION_DATA, [
                        'field' => $identifier,
                        'representation' => $representation->getLabel()
                    ]);
                }

                $removed = $this->getVersionRemoved($annotation);
                if ($removed) {
                    $this->logRemoved($removed, self::CHANGE_REPRESENTATION_DATA, [
                        'field' => $identifier,
                        'representation' => $representation->getLabel()
                    ]);
                }
            }
        }
    }

    /**
     * Compile a changelog for a parsed set of resources.
     *
     * @param array $resources
     * @return void
     */
    private function buildResourceChangelog(array $resources = [])
    {
        foreach ($resources as $group_name => $data) {
            foreach ($data['resources'] as $resource_name => $resource) {
                /** @var Action\Documentation $action */
                foreach ($resource['actions'] as $identifier => $action) {
                    // When was this action introduced?
                    $min_version = $action->getMinimumVersion();
                    if ($min_version) {
                        $min_version = $min_version->getMinimumVersion();
                        $this->logAdded($min_version, self::CHANGE_ACTION, [
                            'method' => $action->getMethod(),
                            'uri' => $action->getUri()->getCleanPath()
                        ]);
                    }

                    // Diff action content types.
                    /** @var ContentTypeAnnotation $content_type */
                    foreach ($action->getContentTypes() as $content_type) {
                        $introduced = $this->getVersionIntroduced($content_type);
                        if ($introduced) {
                            $this->logChanged($introduced, self::CHANGE_CONTENT_TYPE, [
                                'method' => $action->getMethod(),
                                'uri' => $action->getUri()->getCleanPath(),
                                'content_type' => $content_type->getContentType()
                            ]);
                        }
                    }

                    // Diff action `param`, `return` and `throws` annotations.
                    foreach ($action->getAnnotations() as $annotation_name => $annotations) {
                        /** @var Annotation $annotation */
                        foreach ($annotations as $annotation) {
                            if (!$annotation->supportsVersioning()) {
                                continue;
                            }

                            $introduced = $this->getVersionIntroduced($annotation);
                            $removed = $this->getVersionRemoved($annotation);
                            if (!$introduced && !$removed) {
                                continue;
                            }

                            $data = [
                                'method' => $action->getMethod(),
                                'uri' => $action->getUri()->getCleanPath()
                            ];

                            if ($annotation instanceof ParamAnnotation) {
                                $change_identifier = self::CHANGE_ACTION_PARAM;

                                /** @var ParamAnnotation $annotation */
                                $data['parameter'] = $annotation->getField();
                                $data['description'] = $annotation->getDescription();
                            } elseif ($annotation instanceof ReturnAnnotation) {
                                $change_identifier = self::CHANGE_ACTION_RETURN;

                                /** @var ReturnAnnotation $annotation */
                                $data['http_code'] = $annotation->getHttpCode();

                                if ($annotation->getRepresentation()) {
                                    $representation = $annotation->getRepresentation();

                                    /** @var Documentation $representation */
                                    $representation = $this->parsed['representations'][$representation];
                                    $data['representation'] = $representation->getLabel();
                                } else {
                                    $data['representation'] = false;
                                }
                            } elseif ($annotation instanceof ThrowsAnnotation) {
                                $change_identifier = self::CHANGE_ACTION_THROWS;

                                /** @var Documentation $representation */
                                $representation = $this->parsed['representations'][$annotation->getRepresentation()];

                                /** @var ThrowsAnnotation $annotation */
                                $data['http_code'] = $annotation->getHttpCode();
                                $data['representation'] = $representation->getLabel();
                                $data['description'] = $annotation->getDescription();
                            } else {
                                // This annotation isn't yet supported in changelog generation.
                                continue;
                            }

                            if ($introduced) {
                                $this->logAdded($introduced, $change_identifier, $data);
                            }

                            if ($removed) {
                                $this->logRemoved($removed, $change_identifier, $data);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Log an addition into the changelog.
     *
     * @param string $version
     * @param string $identifier
     * @param array $data
     * @return Changelog
     */
    private function logAdded($version, $identifier, array $data = [])
    {
        if ($identifier === self::CHANGE_REPRESENTATION_DATA) {
            $representation = $data['representation'];
            $this->changelog[$version]['added']['representations'][$representation][$identifier][] = $data;
        } else {
            $uri = $data['uri'];
            $this->changelog[$version]['added']['resources'][$uri][$identifier][] = $data;
        }

        return $this;
    }

    /**
     * Log an altered piece of data into the changelog.
     *
     * @param string $version
     * @param string $identifier
     * @param array $data
     * @return Changelog
     */
    private function logChanged($version, $identifier, array $data = [])
    {
        if ($identifier === self::CHANGE_REPRESENTATION_DATA) {
            $representation = $data['representation'];
            $this->changelog[$version]['changed']['representations'][$representation][$identifier] = $data;
        } else {
            $uri = $data['uri'];
            $this->changelog[$version]['changed']['resources'][$uri][$identifier][] = $data;
        }

        return $this;
    }

    /**
     * Log a removal into the changelog.
     *
     * @param string $version
     * @param string $identifier
     * @param array $data
     * @return Changelog
     */
    private function logRemoved($version, $identifier, array $data = [])
    {
        if ($identifier === self::CHANGE_REPRESENTATION_DATA) {
            $representation = $data['representation'];
            $this->changelog[$version]['removed']['representations'][$representation][$identifier][] = $data;
        } else {
            $uri = $data['uri'];
            $this->changelog[$version]['removed']['resources'][$uri][$identifier][] = $data;
        }

        return $this;
    }

    /**
     * Get the version that this annotation was introduced. If it was in the first version of the documented API, this
     * will return false.
     *
     * @param Annotation $annotation
     * @return mixed
     */
    private function getVersionIntroduced(Annotation $annotation)
    {
        $data_version = $annotation->getVersion();
        if (!$data_version) {
            return false;
        }

        $available_in = $this->supported_versions->filter(function ($supported) use ($data_version) {
            if ($data_version->matches($supported['version'])) {
                return $supported['version'];
            }

            return false;
        });

        // What is the first version that this existed in?
        $introduced = $available_in->current()['version'];
        if ($introduced === $this->config->getFirstApiVersion()) {
            return false;
        }

        return $introduced;
    }

    /**
     * Get the version that this annotation was removed. If it still exists in the first version of the documented API,
     * this will return false.
     *
     * @param Annotation $annotation
     * @return mixed
     */
    private function getVersionRemoved(Annotation $annotation)
    {
        $data_version = $annotation->getVersion();
        if (!$data_version) {
            return false;
        }

        $available_in = $this->supported_versions->filter(function ($supported) use ($data_version) {
            if ($data_version->matches($supported['version'])) {
                return $supported['version'];
            }

            return false;
        });

        // What is the most recent version that this was available in?
        $recent_version = $available_in->last()['version'];
        if ($recent_version === $this->config->getLatestApiVersion()) {
            return false;
        }

        $recent_version_key = key(
            $this->supported_versions
                ->matching(new Criteria(Criteria::expr()->eq('version', $recent_version), null))
                ->toArray()
        );

        return $this->supported_versions[++$recent_version_key]['version'];
    }
}
