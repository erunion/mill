<?php
namespace Mill\Generator;

use Mill\Generator;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\ContentTypeAnnotation;
use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Representation\Documentation;
use Mill\Parser\Resource\Action;

class Changelog extends Generator
{
    use Generator\Traits\Markdown;

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
        ksort($this->changelog);
        foreach ($this->changelog as $version => $changes) {
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
        $json = [];

        $changelog = $this->generate();
        foreach ($changelog as $version => $data) {
            foreach ($data as $type => $changes) {
                foreach ($changes as $changeset) {
                    switch ($type) {
                        case 'added':
                            $entry = $this->getEntryForAddedChange($changeset);
                            if ($entry) {
                                $json[$version]['added'][] = $entry;
                            }
                            break;

                        case 'changed':
                            $entry = $this->getEntryForChangedItem($changeset);
                            if ($entry) {
                                $json[$version]['changed'][] = $entry;
                            }
                            break;

                        case 'removed':
                            $entry = $this->getEntryForRemovedItem($changeset);
                            if ($entry) {
                                $json[$version]['removed'][] = $entry;
                            }
                            break;
                    }
                }
            }
        }

        return json_encode($json);
    }

    /**
     * Take compiled API documentation and generate a Markdown-based changelog over the life of the API.
     *
     * @return string
     */
    public function generateMarkdown()
    {
        $markdown = '';

        $api_name = $this->config->getName();
        if (!empty($api_name)) {
            $markdown .= sprintf('# Changelog: %s', $api_name);
            $markdown .= $this->line(2);
        } else {
            $markdown .= sprintf('# Changelog', $api_name);
            $markdown .= $this->line(2);
        }

        $changelog = json_decode($this->generateJson(), true);
        foreach ($changelog as $version => $data) {
            $markdown .= sprintf('## %s', $version);
            $markdown .= $this->line();

            foreach ($data as $type => $changes) {
                $markdown .= sprintf('### %s', ucwords($type));
                $markdown .= $this->line();

                foreach ($changes as $changeset) {
                    $markdown .= sprintf('- %s', $changeset);
                    $markdown .= $this->line();
                }

                $markdown .= $this->line();
            }
        }

        return $markdown;
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
                        'identifier' => $identifier,
                        'representation' => $representation->getLabel()
                    ]);
                }

                $removed = $this->getVersionRemoved($annotation);
                if ($removed) {
                    $this->logRemoved($removed, self::CHANGE_REPRESENTATION_DATA, [
                        'identifier' => $identifier,
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
                                $removed_key = $introduced_key = self::CHANGE_ACTION_PARAM;

                                /** @var ParamAnnotation $annotation */
                                $data['parameter'] = $annotation->getField();
                                $data['description'] = $annotation->getDescription();
                            } elseif ($annotation instanceof ReturnAnnotation) {
                                $removed_key = $introduced_key = self::CHANGE_ACTION_RETURN;

                                /** @var ReturnAnnotation $annotation */
                                $data['http_code'] = $annotation->getHttpCode();
                                $data['representation'] = $annotation->getRepresentation();
                            } elseif ($annotation instanceof ThrowsAnnotation) {
                                $removed_key = $introduced_key = self::CHANGE_ACTION_THROWS;

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
                                $this->logAdded($introduced, $introduced_key, $data);
                            }

                            if ($removed) {
                                $this->logRemoved($removed, $removed_key, $data);
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
     * @param string $key
     * @param array $data
     * @return Changelog
     */
    private function logAdded($version, $key, array $data = [])
    {
        $this->changelog[$version]['added'][] = [
            'key' => $key,
            'data' => $data
        ];

        return $this;
    }

    /**
     * Log an altered piece of data into the changelog.
     *
     * @param string $version
     * @param string $key
     * @param array $data
     * @return Changelog
     */
    private function logChanged($version, $key, array $data = [])
    {
        $this->changelog[$version]['changed'][] = [
            'key' => $key,
            'data' => $data
        ];

        return $this;
    }

    /**
     * Log a removal into the changelog.
     *
     * @param string $version
     * @param string $key
     * @param array $data
     * @return Changelog
     */
    private function logRemoved($version, $key, array $data = [])
    {
        $this->changelog[$version]['removed'][] = [
            'key' => $key,
            'data' => $data
        ];

        return $this;
    }

    /**
     * Get a changelog entry for a changeset that was added into the API.
     *
     * @param array $changeset
     * @return null|string
     */
    private function getEntryForAddedChange(array $changeset)
    {
        $description = null;

        // `CHANGE_CONTENT_TYPE` is missing from this switch because it's a required annotation on resource actions,
        // so it'll never be "added" in a changelog, just "changed".
        switch ($changeset['key']) {
            case self::CHANGE_ACTION:
                $description = sprintf(
                    '%s on `%s` was added.',
                    $changeset['data']['method'],
                    $changeset['data']['uri']
                );
                break;

            case self::CHANGE_ACTION_PARAM:
                $description = sprintf(
                    'A `%s` request parameter was added to %s on `%s`.',
                    $changeset['data']['parameter'],
                    $changeset['data']['method'],
                    $changeset['data']['uri']
                );
                break;

            case self::CHANGE_ACTION_RETURN:
                if ($changeset['data']['representation']) {
                    $description = sprintf(
                        '%s on `%s` now return a `%s` with a `%s` representation.',
                        $changeset['data']['method'],
                        $changeset['data']['uri'],
                        $changeset['data']['http_code'],
                        $changeset['data']['representation']
                    );
                } else {
                    $description = sprintf(
                        '%s on `%s` now returns a `%s`.',
                        $changeset['data']['method'],
                        $changeset['data']['uri'],
                        $changeset['data']['http_code']
                    );
                }
                break;

            case self::CHANGE_ACTION_THROWS:
                $description = sprintf(
                    '%s on `%s` will now return a `%s` with a `%s` representation: %s',
                    $changeset['data']['method'],
                    $changeset['data']['uri'],
                    $changeset['data']['http_code'],
                    $changeset['data']['representation'],
                    $changeset['data']['description']
                );
                break;

            case self::CHANGE_REPRESENTATION_DATA:
                $description = sprintf(
                    '`%s` has been added to the `%s` representation.',
                    $changeset['data']['identifier'],
                    $changeset['data']['representation']
                );
                break;
        }

        return $description;
    }

    /**
     * Get a changelog entry for a changeset that was changed in the API.
     *
     * @param array $changeset
     * @return null|string
     */
    private function getEntryForChangedItem(array $changeset)
    {
        $description = null;

        // Due to versioning restrictions in the Mill syntax (that will be fixed), only `@api-contentType` annotations
        // will generate a "changed" entry in the changelog.
        switch ($changeset['key']) {
            case self::CHANGE_CONTENT_TYPE:
                $description = sprintf(
                    '%s on `%s` will now return a `%s` content type.',
                    $changeset['data']['method'],
                    $changeset['data']['uri'],
                    $changeset['data']['content_type']
                );
                break;
        }

        return $description;
    }

    /**
     * Get a changelog entry for a changeset that was removed from the API.
     *
     * @param array $changeset
     * @return null|string
     */
    private function getEntryForRemovedItem(array $changeset)
    {
        $description = null;

        switch ($changeset['key']) {
            case self::CHANGE_REPRESENTATION_DATA:
                $description = sprintf(
                    '`%s` has been removed from the `%s` representation.',
                    $changeset['data']['identifier'],
                    $changeset['data']['representation']
                );
                break;
            case self::CHANGE_ACTION_PARAM:
                $description = sprintf(
                    'The `%s` request parameter has been removed from %s requests on `%s`.',
                    $changeset['data']['parameter'],
                    $changeset['data']['method'],
                    $changeset['data']['uri']
                );
                break;
            case self::CHANGE_ACTION_RETURN:
                if ($changeset['data']['representation']) {
                    $description = sprintf(
                        '%s on `%s` no longer will return a `%s` with a `%s` representation.',
                        $changeset['data']['method'],
                        $changeset['data']['uri'],
                        $changeset['data']['http_code'],
                        $changeset['data']['representation']
                    );
                } else {
                    $description = sprintf(
                        '%s on `%s` no longer will return a `%s`.',
                        $changeset['data']['method'],
                        $changeset['data']['uri'],
                        $changeset['data']['http_code']
                    );
                }
                break;
            case self::CHANGE_ACTION_THROWS:
                $description = sprintf(
                    '%s on `%s` longer will return a `%s` with a `%s` representation: %s',
                    $changeset['data']['method'],
                    $changeset['data']['uri'],
                    $changeset['data']['http_code'],
                    $changeset['data']['representation'],
                    $changeset['data']['description']
                );
                break;
        }

        return $description;
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

        $available_in = [];
        foreach ($this->supported_versions as $version) {
            if ($data_version->matches($version)) {
                $available_in[] = $version;
            }
        }

        // What is the first version that this existed in?
        $introduced = current($available_in);
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

        $available_in = [];
        foreach ($this->supported_versions as $version) {
            if ($data_version->matches($version)) {
                $available_in[] = $version;
            }
        }

        // What is the most recent version that this was available in?
        $recent_version = end($available_in);
        if ($recent_version === $this->config->getLatestApiVersion()) {
            return false;
        }

        $recent_version_key = array_flip($this->supported_versions)[$recent_version];
        $removed = $this->supported_versions[++$recent_version_key];
        if ($removed !== $this->config->getLatestApiVersion()) {
            return false;
        }

        return $removed;
    }
}
