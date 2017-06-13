<?php
namespace Mill\Generator\Changelog;

use Mill\Generator;
use Mill\Generator\Changelog;
use StringTemplate\Engine;

class Json extends Generator
{
    /**
     * Template string engine.
     *
     * @var Engine
     */
    protected $template_engine;

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
                'added' => '`{uri}` has been added with support for the following HTTP methods:',
            ],
            Changelog::CHANGE_ACTION_PARAM => [
                'added' => 'The following parameters have been added to {method} on `{uri}`:',
                'removed' => 'The following parameters have been removed to {method} on `{uri}`:'
            ],
            Changelog::CHANGE_ACTION_RETURN => [
                'added' => 'The {method} on `{uri}` will now return the following responses:',
                'removed' => 'The {method} on `{uri}` no longer returns the following responses:'
            ],
            Changelog::CHANGE_ACTION_THROWS => [
                'added' => 'The {method} on `{uri}` can now throw the following errors:',
                'removed' => 'The {method} on `{uri}` will no longer throw the following errors:'
            ],
            Changelog::CHANGE_CONTENT_TYPE => [
                'changed' => '`{uri}` now returns a `{content_type}` Content-Type header on the following HTTP ' .
                    'methods:',
            ],
            Changelog::CHANGE_REPRESENTATION_DATA => [
                'added' => 'The following fields have been added to the `{representation}` representation:',
                'removed' => 'The following fields have been removed from the `{representation}` representation:'
            ]
        ],
        'singular' => [
            Changelog::CHANGE_ACTION => [
                'added' => '{method} on `{uri}` was added.'
            ],
            Changelog::CHANGE_ACTION_PARAM => [
                'added' => 'A `{parameter}` request parameter was added to {method} on `{uri}`.',
                'removed' => 'The `{parameter}` request parameter has been removed from {method} requests on `{uri}`.'
            ],
            Changelog::CHANGE_ACTION_RETURN => [
                'added' => '{method} on `{uri}` now returns a `{http_code}` with a `{representation}` representation.',
                'removed' => '{method} on `{uri}` no longer returns a `{http_code}` with a `{representation}` ' .
                    'representation.'
            ],
            Changelog::CHANGE_ACTION_RETURN . '_no_representation' => [
                'added' => '{method} on `{uri}` now returns a `{http_code}`.',
                'removed' => '{method} on `{uri}` no longer will return a `{http_code}`.'
            ],
            Changelog::CHANGE_ACTION_THROWS => [
                'added' => '{method} on `{uri}` now returns a `{http_code}` with a `{representation}` ' .
                    'representation: {description}',
                'removed' => '{method} on `{uri}` longer will return a `{http_code}` with a `{representation}` ' .
                    'representation: {description}'
            ],
            Changelog::CHANGE_CONTENT_TYPE => [
                'changed' => '{method} on `{uri}` now returns a `{content_type}` Content-Type header.'
            ],
            Changelog::CHANGE_REPRESENTATION_DATA => [
                'added' => '`{field}` has been added to the `{representation}` representation.',
                'removed' => '`{field}` has been removed from the `{representation}` representation.'
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
        $this->template_engine = new Engine;
        $json = [];

        foreach ($this->changelog as $version => $version_changes) {
            foreach ($version_changes as $change_type => $data) {
                foreach ($data as $section => $section_changes) {
                    foreach ($section_changes as $header => $changes) {
                        foreach ($changes as $identifier => $changesets) {
                            if ($change_type === 'added' || $change_type === 'removed') {
                                $entry = $this->getEntryForAddedOrRemovedChange(
                                    $change_type,
                                    $header,
                                    $identifier,
                                    $changesets
                                );
                            } else {
                                $entry = $this->getEntryForChangedItem($header, $identifier, $changesets);
                            }

                            if ($entry) {
                                $json[$version][$change_type][$section][] = $entry;
                            }
                        }
                    }
                }
            }
        }

        return json_encode($json);
    }

    /**
     * Get a changelog entry for a changeset that was added into the API.
     *
     * @param string $change_type
     * @param string $header
     * @param string $identifier
     * @param array $changesets
     * @return bool|string|array
     */
    private function getEntryForAddedOrRemovedChange($change_type, $header, $identifier, array $changesets)
    {
        if (count($changesets) > 1) {
            switch ($identifier) {
                case Changelog::CHANGE_ACTION:
                    $methods = [];
                    foreach ($changesets as $change) {
                        $methods[] = sprintf('`%s`', $change['method']);
                    }

                    $template = $this->changeset_templates['plural'][$identifier][$change_type];
                    return [
                        [
                            $this->template_engine->render($template, [
                                'uri' => $header
                            ]),
                            $methods
                        ]
                    ];
                    break;

                case Changelog::CHANGE_ACTION_PARAM:
                    $methods = [];
                    foreach ($changesets as $change) {
                        $methods[$change['method']][] = sprintf('`%s`', $change['parameter']);
                    }

                    $entry = [];
                    foreach ($methods as $method => $params) {
                        if (count($params) > 1) {
                            $template = $this->changeset_templates['plural'][$identifier][$change_type];
                            $entry[] = [
                                $this->template_engine->render($template, [
                                    'method' => $method,
                                    'uri' => $header
                                ]),
                                $params
                            ];

                            continue;
                        }

                        $template = $this->changeset_templates['singular'][$identifier][$change_type];
                        $entry[] = $this->template_engine->render($template, [
                            'parameter' => rtrim(ltrim(array_shift($params), '`'), '`'),
                            'method' => $method,
                            'uri' => $header
                        ]);
                    }

                    return $entry;
                    break;

                case Changelog::CHANGE_ACTION_RETURN:
                    $methods = [];
                    foreach ($changesets as $change) {
                        $methods[$change['method']][] = $change;
                    }

                    $entry = [];
                    foreach ($methods as $method => $changes) {
                        if (count($changes) > 1) {
                            $returns = [];
                            foreach ($changes as $change) {
                                if ($change['representation']) {
                                    $returns[] = sprintf(
                                        '`%s` with a `%s` representation',
                                        $change['http_code'],
                                        $change['representation']
                                    );
                                } else {
                                    $returns[] = sprintf('`%s`', $change['http_code']);
                                }
                            }

                            $template = $this->changeset_templates['plural'][$identifier][$change_type];
                            $entry[] = [
                                $this->template_engine->render($template, [
                                    'method' => $method,
                                    'uri' => $header
                                ]),
                                $returns
                            ];

                            continue;
                        }

                        $change = array_shift($changes);
                        $template = $this->changeset_templates['singular'][$identifier][$change_type];
                        $entry[] = $this->template_engine->render($template, $change);
                    }

                    return $entry;
                    break;

                case Changelog::CHANGE_ACTION_THROWS:
                    $methods = [];
                    foreach ($changesets as $change) {
                        $methods[$change['method']][] = $change;
                    }

                    $entry = [];
                    foreach ($methods as $method => $changes) {
                        if (count($changes) > 1) {
                            $errors = [];
                            foreach ($changes as $change) {
                                $errors[] = sprintf(
                                    '`%s` with a `%s` representation: %s',
                                    $change['http_code'],
                                    $change['representation'],
                                    $change['description']
                                );
                            }

                            $template = $this->changeset_templates['plural'][$identifier][$change_type];
                            $entry[] = [
                                $this->template_engine->render($template, [
                                    'method' => $method,
                                    'uri' => $header
                                ]),
                                $errors
                            ];

                            continue;
                        }

                        $change = array_shift($changes);
                        $template = $this->changeset_templates['singular'][$identifier][$change_type];
                        $entry[] = $this->template_engine->render($template, $change);
                    }

                    return $entry;
                    break;

                case Changelog::CHANGE_REPRESENTATION_DATA:
                    $fields = [];
                    foreach ($changesets as $change) {
                        $fields[] = sprintf('`%s`', $change['field']);
                    }

                    $template = $this->changeset_templates['plural'][$identifier][$change_type];
                    return [
                        [
                            $this->template_engine->render($template, [
                                'representation' => $header
                            ]),
                            $fields
                        ]
                    ];
                    break;
            }

            return false;
        }

        $changeset = array_shift($changesets);
        switch ($identifier) {
            case Changelog::CHANGE_ACTION:
            case Changelog::CHANGE_ACTION_PARAM:
            case Changelog::CHANGE_ACTION_THROWS:
            case Changelog::CHANGE_REPRESENTATION_DATA:
                $template = $this->changeset_templates['singular'][$identifier][$change_type];
                return $this->template_engine->render($template, $changeset);
                break;

            case Changelog::CHANGE_ACTION_RETURN:
                if ($changeset['representation']) {
                    $template = $this->changeset_templates['singular'][Changelog::CHANGE_ACTION_RETURN][$change_type];
                } else {
                    $template_key = Changelog::CHANGE_ACTION_RETURN . '_no_representation';
                    $template = $this->changeset_templates['singular'][$template_key][$change_type];
                }

                return $this->template_engine->render($template, $changeset);
                break;
        }

        return false;
    }

    /**
     * Get a changelog entry for a changeset that was changed in the API.
     *
     * @param string $header
     * @param string $identifier
     * @param array $changesets
     * @return bool|string|array
     */
    private function getEntryForChangedItem($header, $identifier, array $changesets)
    {
        // Due to versioning restrictions in the Mill syntax (that will be fixed), only `@api-contentType` annotations
        // will generate a "changed" entry in the changelog.
        switch ($identifier) {
            case Changelog::CHANGE_CONTENT_TYPE:
                if (count($changesets) > 1) {
                    $content_types = [];
                    foreach ($changesets as $changeset) {
                        $content_types[$changeset['content_type']][] = sprintf('`%s`', $changeset['method']);
                    }

                    $entry = [];
                    $template = $this->changeset_templates['plural'][$identifier]['changed'];
                    foreach ($content_types as $content_type => $methods) {
                        $entry[] = [
                            $this->template_engine->render($template, [
                                'uri' => $header,
                                'content_type' => $content_type
                            ]),
                            $methods
                        ];
                    }

                    return $entry;
                }

                $changeset = array_shift($changesets);
                $template = $this->changeset_templates['singular'][$identifier]['changed'];
                return $this->template_engine->render($template, $changeset);
                break;
        }

        return false;
    }
}
