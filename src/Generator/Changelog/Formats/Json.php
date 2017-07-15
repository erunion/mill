<?php
namespace Mill\Generator\Changelog\Formats;

use Mill\Generator;
use Mill\Generator\Changelog;
use Mill\Generator\Traits\TextRendering;

class Json extends Generator
{
    use TextRendering {
        renderText as protected compileTemplate;
    }

    const CSS_NAMESPACE = 'mill-changelog';

    /**
     * Generated changelog.
     *
     * @var array
     */
    protected $changelog = [];

    /**
     * Changelog changeset templates.
     *
     * @var array
     */
    protected $changeset_templates = [
        'plural' => [
            Changelog::CHANGE_ACTION => [
                'added' => '{uri} has been added with support for the following HTTP methods:',
            ],
            Changelog::CHANGE_ACTION_PARAM => [
                'added' => 'The following parameters have been added to {method} on {uri}:',
                'removed' => 'The following parameters have been removed to {method} on {uri}:'
            ],
            Changelog::CHANGE_ACTION_RETURN => [
                'added' => 'The {method} on {uri} will now return the following responses:',
                'removed' => 'The {method} on {uri} no longer returns the following responses:'
            ],
            Changelog::CHANGE_ACTION_THROWS => [
                'added' => '{uri} will now throw the following errors on {method} requests:',
                'removed' => '{uri} will no longer throw the following errors on {method} requests:'
            ],
            /*Changelog::CHANGE_CONTENT_TYPE => [
                'changed' => '{uri} now returns a {content_type} Content-Type header on the following HTTP ' .
                    'methods:',
            ],*/
            Changelog::CHANGE_REPRESENTATION_DATA => [
                'added' => 'The {representation} has added the following fields:',
                'removed' => 'The {representation} has removed the following fields:'
            ]
        ],
        'singular' => [
            Changelog::CHANGE_ACTION => [
                'added' => '{method} on {uri} was added.'
            ],
            Changelog::CHANGE_ACTION_PARAM => [
                'added' => 'A {parameter} request parameter was added to {method} on {uri}.',
                'removed' => 'The {parameter} request parameter has been removed from {method} requests on {uri}.'
            ],
            Changelog::CHANGE_ACTION_RETURN => [
                'added' => 'On {uri}, {method} requests now returns a {http_code} with a {representation} ' .
                    'representation.',
                'removed' => 'On {uri}, {method} requests no longer returns a {http_code} with a {representation} ' .
                    'representation.'
            ],
            Changelog::CHANGE_ACTION_RETURN . '_no_representation' => [
                'added' => '{method} on {uri} now returns a {http_code}.',
                'removed' => '{method} on {uri} no longer will return a {http_code}.'
            ],
            Changelog::CHANGE_ACTION_THROWS => [
                'added' => 'On {method} requests to {uri}, a {http_code} with a {representation} representation ' .
                    'will now be returned: {description}',
                'removed' => '{method} requests to {uri} longer will return a {http_code} with a {representation} ' .
                    'representation: {description}'
            ],
            Changelog::CHANGE_CONTENT_TYPE => [
                'changed' => 'On {uri}, {method} requests will return a {content_type} Content-Type header.'
            ],
            Changelog::CHANGE_REPRESENTATION_DATA => [
                'added' => '{field} has been added to the {representation} representation.',
                'removed' => '{field} has been removed from the {representation} representation.'
            ]
        ]
    ];

    /**
     * Set the current changelog we're going to build a representation for.
     *
     * @param array $changelog
     * @return Json
     */
    public function setChangelog(array $changelog = [])
    {
        $this->changelog = $changelog;
        return $this;
    }

    /**
     * Take compiled API documentation and generate a JSON-encoded changelog over the life of the API.
     *
     * @return string
     */
    public function generate()
    {
        $json = [];

        foreach ($this->changelog as $version => $version_changes) {
            foreach ($version_changes as $definition => $data) {
                if ($definition === '_details') {
                    $json[$version][$definition] = $data;
                    continue;
                }

                foreach ($data as $type => $changesets) {
                    if ($type === 'resources') {
                        $entries = $this->parseResourceChangesets($definition, $changesets);
                    } else {
                        $entries = $this->parseRepresentationChangesets($definition, $changesets);
                    }

                    $json[$version][$definition][$type] = $entries;
                }
            }
        }

        return json_encode($json);
    }

    /**
     * Parse representation changesets.
     *
     * @param string $definition
     * @param array $changesets
     * @return array
     */
    private function parseRepresentationChangesets($definition, array $changesets = [])
    {
        $entries = [];
        foreach ($changesets as $representation => $change_types) {
            foreach ($change_types as $change_type => $hashes) {
                foreach ($hashes as $hash => $changes) {
                    if ($definition === 'added' || $definition === 'removed') {
                        $entry = $this->getEntryForAddedOrRemovedChange($definition, $change_type, $changes);
                    } else {
                        $entry = $this->getEntryForChangedItem($definition, $change_type, $changes);
                    }

                    // Reduce some unnecessary nesting of changeset strings.
                    if (is_array($entry) && count($entry) === 1) {
                        $entry = array_shift($entry);
                    }

                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    /**
     * Parse resource changesets.
     *
     * @param string $definition
     * @param array $changesets
     * @return array
     */
    private function parseResourceChangesets($definition, array $changesets = [])
    {
        $entries = [];
        foreach ($changesets as $group => $data) {
            $group_entry = [
                $this->renderText('The following {group} resources have ' . $definition . ':', [
                    'group' => $group
                ]),
                [] // Group-related entries will be nested here.
            ];

            foreach ($data as /*$uri => */$change_types) {
                foreach ($change_types as $change_type => $hashes) {
                    foreach ($hashes as /*$hash => */$changes) {
                        if ($definition === 'added' || $definition === 'removed') {
                            $entry = $this->getEntryForAddedOrRemovedChange($definition, $change_type, $changes);
                        } else {
                            $entry = $this->getEntryForChangedItem($definition, $change_type, $changes);
                        }

                        // Reduce some unnecessary nesting of changeset strings.
                        if (is_array($entry) && count($entry) === 1) {
                            $entry = array_shift($entry);
                        }

                        $group_entry[1][] = $entry;
                    }
                }
            }

            $entries[] = $group_entry;
        }

        return $entries;
    }

    /**
     * Get a changelog entry for a changeset that was added into the API.
     *
     * @param string $definition
     * @param string $change_type
     * @param array $changes
     * @return bool|string|array
     * @throws \Exception If an unsupported definition + change type was supplied.
     */
    private function getEntryForAddedOrRemovedChange($definition, $change_type, array $changes)
    {
        if (count($changes) > 1) {
            switch ($change_type) {
                case Changelog::CHANGE_ACTION:
                    $methods = [];
                    foreach ($changes as $change) {
                        $methods[] = $this->renderText('{method}', $change);
                    }

                    $template = $this->changeset_templates['plural'][$change_type][$definition];
                    return [
                        [
                            // Changes are grouped by URIs so it's safe to just pull the first URI here.
                            $this->renderText($template, [
                                'uri' => $changes[0]['uri']
                            ]),
                            $methods
                        ]
                    ];
                    break;

                case Changelog::CHANGE_ACTION_PARAM:
                    $methods = [];
                    foreach ($changes as $change) {
                        $methods[$change['method']][] = $this->renderText('{parameter}', $change);
                    }

                    $entry = [];
                    foreach ($methods as $method => $params) {
                        if (count($params) > 1) {
                            $template = $this->changeset_templates['plural'][$change_type][$definition];
                            $entry[] = [
                                $this->renderText($template, [
                                    'method' => $method,
                                    'uri' => $changes[0]['uri']
                                ]),
                                $params
                            ];

                            continue;
                        }

                        $template = $this->changeset_templates['singular'][$change_type][$definition];
                        $entry[] = $this->renderText($template, [
                            'parameter' => rtrim(ltrim(array_shift($params), '`'), '`'),
                            'method' => $method,
                            'uri' => $changes[0]['uri']
                        ]);
                    }

                    return $entry;
                    break;

                case Changelog::CHANGE_ACTION_RETURN:
                    $methods = [];
                    foreach ($changes as $change) {
                        $methods[$change['method']][] = $change;
                    }

                    $entries = [];
                    foreach ($methods as $method => $changes) {
                        if (count($changes) > 1) {
                            $returns = [];
                            foreach ($changes as $change) {
                                if ($change['representation']) {
                                    $returns[] = $this->renderText(
                                        '{http_code} with a {representation} representation',
                                        $change
                                    );
                                } else {
                                    $returns[] = $this->renderText('{http_code}', $change);
                                }
                            }

                            $template = $this->changeset_templates['plural'][$change_type][$definition];
                            $entries[] = [
                                $this->renderText($template, [
                                    'method' => $method,
                                    'uri' => $changes[0]['uri']
                                ]),
                                $returns
                            ];

                            continue;
                        }

                        $change = array_shift($changes);
                        $template = $this->changeset_templates['singular'][$change_type][$definition];
                        $entries[] = $this->renderText($template, $change);
                    }

                    return $entries;
                    break;

                case Changelog::CHANGE_ACTION_THROWS:
                    $methods = [];
                    foreach ($changes as $change) {
                        $methods[$change['method']][] = $change;
                    }

                    $entries = [];
                    foreach ($methods as $method => $changes) {
                        if (count($changes) > 1) {
                            $errors = [];
                            foreach ($changes as $change) {
                                $errors[] = $this->renderText(
                                    '{http_code} with a {representation} representation: {description}',
                                    $change
                                );
                            }

                            $template = $this->changeset_templates['plural'][$change_type][$definition];
                            $entries[] = [
                                $this->renderText($template, [
                                    'method' => $method,
                                    'uri' => array_shift($changes)['uri']
                                ]),
                                array_unique($errors)
                            ];
                            continue;
                        }

                        $change = array_shift($changes);
                        $template = $this->changeset_templates['singular'][$change_type][$definition];
                        $entries[] = $this->renderText($template, $change);
                    }

                    return $entries;
                    break;

                case Changelog::CHANGE_REPRESENTATION_DATA:
                    $fields = [];
                    foreach ($changes as $change) {
                        $fields[] = $this->renderText('{field}', $change);
                    }

                    $template = $this->changeset_templates['plural'][$change_type][$definition];
                    return [
                        $this->renderText($template, array_shift($changes)),
                        $fields
                    ];
                    break;

                default:
                    throw new \Exception($definition . ' `' . $change_type . '` changes are not yet supported.');
            }

            return false;
        }

        $changeset = array_shift($changes);
        switch ($change_type) {
            case Changelog::CHANGE_ACTION:
            case Changelog::CHANGE_ACTION_PARAM:
            case Changelog::CHANGE_ACTION_THROWS:
            case Changelog::CHANGE_REPRESENTATION_DATA:
                $template = $this->changeset_templates['singular'][$change_type][$definition];
                return $this->renderText($template, $changeset);
                break;

            case Changelog::CHANGE_ACTION_RETURN:
                if ($changeset['representation']) {
                    $template = $this->changeset_templates['singular'][Changelog::CHANGE_ACTION_RETURN][$definition];
                } else {
                    $template_key = Changelog::CHANGE_ACTION_RETURN . '_no_representation';
                    $template = $this->changeset_templates['singular'][$template_key][$definition];
                }

                return $this->renderText($template, $changeset);
                break;

            default:
                throw new \Exception($definition . ' `' . $change_type . '` changes are not yet supported.');
        }

        return false;
    }

    /**
     * Get a changelog entry for a changeset that was changed in the API.
     *
     * @param string $definition
     * @param string $change_type
     * @param array $changes
     * @return bool|string|array
     * @throws \Exception If an unsupported definition + change type was supplied.
     */
    private function getEntryForChangedItem($definition, $change_type, array $changes)
    {
        // Due to versioning restrictions in the Mill syntax (that will be fixed), only `@api-contentType` annotations
        // will generate a "changed" entry in the changelog.
        switch ($change_type) {
            case Changelog::CHANGE_CONTENT_TYPE:
                if (count($changes) > 1) {
                    $uris = array_map(function ($change) {
                        return $change['uri'];
                    }, $changes);

                    // Changes are hashed and grouped by their hashes (sans URI), so it's safe to just pass
                    // along this change into the template engine to build a string.
                    $change = array_shift($changes);
                    $change['uri'] = $uris;
                } else {
                    $change = array_shift($changes);
                }

                $template = $this->changeset_templates['singular'][$change_type][$definition];
                return $this->renderText($template, $change);
                break;

            default:
                throw new \Exception($definition . ' `' . $change_type . '` changes are not yet supported.');
        }

        return false;
    }

    /**
     * Render a template with some content.
     *
     * @param string $template
     * @param array $content
     * @return string
     */
    protected function renderText($template, array $content)
    {
        $searches = [];
        $replacements = [];
        foreach ($content as $key => $value) {
            $data_attribute_key = str_replace('_', '-', $key);

            switch ($key) {
                case 'content_type':
                case 'field':
                case 'group':
                case 'http_code':
                case 'method':
                case 'parameter':
                case 'representation':
                case 'uri':
                    $searches[] = '{' . $key . '}';
                    if (is_array($value)) {
                        $replacements[] = $this->joinWords(
                            array_map(function ($val) use ($data_attribute_key, $key) {
                                return sprintf(
                                    '<span class="{css_namespace}_%s" data-mill-%s="%s">%s</span>',
                                    $key,
                                    $data_attribute_key,
                                    $val,
                                    $val
                                );
                            }, $value)
                        );
                    } else {
                        $replacements[] = sprintf(
                            '<span class="{css_namespace}_%s" data-mill-%s="{%s}">{%s}</span>',
                            $key,
                            $data_attribute_key,
                            $key,
                            $key
                        );
                    }
                    break;

                case 'description':
                default:
                    // do nothing
            }
        }

        $template = str_replace($searches, $replacements, $template);

        $content['css_namespace'] = self::CSS_NAMESPACE;

        return $this->compileTemplate($template, $content);
    }
}
