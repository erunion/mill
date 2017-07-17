<?php
namespace Mill\Generator;

use Mill\Generator;
use Mill\Generator\Changelog\Formats\Json;
use Mill\Generator\Changelog\Formats\Markdown;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\ContentTypeAnnotation;
use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Representation\Documentation;
use Mill\Parser\Resource\Action;

class Changelog extends Generator
{
    const CHANGESET_TYPE_ACTION = 'action';
    const CHANGESET_TYPE_ACTION_PARAM = 'action_param';
    const CHANGESET_TYPE_ACTION_RETURN = 'action_return';
    const CHANGESET_TYPE_ACTION_THROWS = 'action_throws';
    const CHANGESET_TYPE_CONTENT_TYPE = 'content_type';
    const CHANGESET_TYPE_REPRESENTATION_DATA = 'representation_data';

    const DEFINITION_ADDED = 'added';
    const DEFINITION_CHANGED = 'changed';
    const DEFINITION_REMOVED = 'removed';

    const FORMAT_JSON = 'json';
    const FORMAT_MARKDOWN = 'markdown';

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
            $representation_name = $representation->getLabel();
            $content = $representation->getRawContent();

            /** @var Annotation $annotation */
            foreach ($content as $field => $annotation) {
                $introduced = $this->getVersionIntroduced($annotation);
                if ($introduced) {
                    $this->record(
                        self::DEFINITION_ADDED,
                        $introduced,
                        self::CHANGESET_TYPE_REPRESENTATION_DATA,
                        $representation_name,
                        [
                            'field' => $field,
                            'representation' => $representation_name
                        ]
                    );
                }

                $removed = $this->getVersionRemoved($annotation);
                if ($removed) {
                    $this->record(
                        self::DEFINITION_REMOVED,
                        $removed,
                        self::CHANGESET_TYPE_REPRESENTATION_DATA,
                        $representation_name,
                        [
                            'field' => $field,
                            'representation' => $representation_name
                        ]
                    );
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
                        $this->record(
                            self::DEFINITION_ADDED,
                            $min_version,
                            self::CHANGESET_TYPE_ACTION,
                            $group_name,
                            [
                                'method' => $action->getMethod(),
                                'uri' => $action->getUri()->getCleanPath()
                            ]
                        );
                    }

                    // Diff action content types.
                    /** @var ContentTypeAnnotation $content_type */
                    foreach ($action->getContentTypes() as $content_type) {
                        $introduced = $this->getVersionIntroduced($content_type);
                        if ($introduced) {
                            $this->record(
                                self::DEFINITION_CHANGED,
                                $introduced,
                                self::CHANGESET_TYPE_CONTENT_TYPE,
                                $group_name,
                                [
                                    'method' => $action->getMethod(),
                                    'uri' => $action->getUri()->getCleanPath(),
                                    'content_type' => $content_type->getContentType()
                                ]
                            );
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
                                $change_type = self::CHANGESET_TYPE_ACTION_PARAM;

                                /** @var ParamAnnotation $annotation */
                                $data['parameter'] = $annotation->getField();
                                $data['description'] = $annotation->getDescription();
                            } elseif ($annotation instanceof ReturnAnnotation) {
                                $change_type = self::CHANGESET_TYPE_ACTION_RETURN;

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
                                $change_type = self::CHANGESET_TYPE_ACTION_THROWS;

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
                                $this->record(
                                    self::DEFINITION_ADDED,
                                    $introduced,
                                    $change_type,
                                    $group_name,
                                    $data
                                );
                            }

                            if ($removed) {
                                $this->record(
                                    self::DEFINITION_REMOVED,
                                    $removed,
                                    $change_type,
                                    $group_name,
                                    $data
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Record an entry into the changelog.
     *
     * @param string $definition
     * @param string $version
     * @param string $change_type
     * @param string|null $group
     * @param array $data
     * @return Changelog
     */
    private function record($definition, $version, $change_type, $group = null, array $data = [])
    {
        // Since groups can be nested, let's throw changes under the top-level group.
        if (!is_null($group)) {
            $group = explode('\\', $group);
            $group = array_shift($group);
        }

        $hash = $this->hashChangeset($change_type, $data);

        if ($change_type === self::CHANGESET_TYPE_REPRESENTATION_DATA) {
            $this->changelog[$version][$definition]['representations'][$group][$change_type][$hash][] = $data;
        } else {
            $this->changelog[$version][$definition]['resources'][$group][$data['uri']][$change_type][$hash][] = $data;
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

        $available_in = array_filter($this->supported_versions, function ($supported) use ($data_version) {
            if ($data_version->matches($supported['version'])) {
                return $supported['version'];
            }

            return false;
        });

        // What is the first version that this existed in?
        $introduced = current($available_in)['version'];
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

        $available_in = array_filter($this->supported_versions, function ($supported) use ($data_version) {
            if ($data_version->matches($supported['version'])) {
                return $supported['version'];
            }

            return false;
        });

        // What is the most recent version that this was available in?
        $recent_version = end($available_in)['version'];
        if ($recent_version === $this->config->getLatestApiVersion()) {
            return false;
        }

        $recent_version_key = key(
            array_filter($this->supported_versions, function ($supported) use ($recent_version) {
                return $supported['version'] == $recent_version;
            })
        );

        return $this->supported_versions[++$recent_version_key]['version'];
    }

    /**
     * Hash a given changeset for de-duping purposes.
     *
     * @param string $change_type
     * @param array $data
     * @return string
     */
    private function hashChangeset($change_type, array $data)
    {
        $hash_data = [];

        // For changesets, the hash is grouped based on an indexed piece of content. Here we're excluding those indexes
        // from the to-be-generated hashes so we can get like-hashes across multiple pieces of data. Without this, we
        // wouldn't be able to do proper duplicate detection.
        switch ($change_type) {
            case self::CHANGESET_TYPE_ACTION:
                $hash_data['uri'] = $data['uri'];
                break;

            case self::CHANGESET_TYPE_ACTION_PARAM:
                $hash_data['method'] = $data['method'];
                $hash_data['uri'] = $data['uri'];
                break;

            case self::CHANGESET_TYPE_ACTION_RETURN:
                $hash_data['method'] = $data['method'];
                $hash_data['uri'] = $data['uri'];
                break;

            case self::CHANGESET_TYPE_ACTION_THROWS:
                $hash_data['method'] = $data['method'];
                $hash_data['uri'] = $data['uri'];
                break;

            case self::CHANGESET_TYPE_CONTENT_TYPE:
                $hash_data['method'] = $data['method'];
                break;

            case self::CHANGESET_TYPE_REPRESENTATION_DATA:
                $hash_data['representation'] = $data['representation'];
                break;

            default:
                $hash_data = $data;
                unset($hash_data['uri']);
        }

        return substr(sha1(serialize($hash_data)), 0, 10);
    }
}
