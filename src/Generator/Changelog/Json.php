<?php
namespace Mill\Generator\Changelog;

use Mill\Generator\Changelog;
use StringTemplate\Engine;

class Json extends Changelog
{
    /**
     * Template string engine.
     *
     * @var Engine
     */
    protected $template_engine;

    /**
     * Changelog changeset templates.
     *
     * @var array
     */
    protected $changeset_templates = [
        'plural' => [
            self::CHANGE_ACTION => [
                'added' => '`{uri}` has been added with support for the following HTTP methods:'
            ],
            self::CHANGE_ACTION_PARAM => [
                'added' => 'The following parameters have been added to {method} on `{uri}`:'
            ],
            self::CHANGE_CONTENT_TYPE => [
                'changed' => '`{uri}` now returns a `{content_type}` Content-Type header on the following HTTP ' .
                    'methods:'
            ],
            self::CHANGE_REPRESENTATION_DATA => [
                'added' => 'The following fields have been added to the `{representation}` representation:'
            ]
        ],
        'singular' => [
            self::CHANGE_ACTION => [
                'added' => '{method} on `{uri}` was added.'
            ],
            self::CHANGE_ACTION_PARAM => [
                'added' => 'A `{parameter}` request parameter was added to {method} on `{uri}`.',
                'removed' => 'The `{parameter}` request parameter has been removed from {method} requests on `{uri}`.'
            ],
            self::CHANGE_ACTION_RETURN => [
                'added' => '{method} on `{uri}` now returns a `{http_code}` with a `{representation}` representation.',
                'removed' => '{method} on `{uri}` no longer will return a `{http_code}` with a `{representation}` ' .
                    'representation.'
            ],
            self::CHANGE_ACTION_RETURN . '_no_representation' => [
                'added' => '{method} on `{uri}` now returns a `{http_code}`.',
                'removed' => '{method} on `{uri}` no longer will return a `{http_code}`.'
            ],
            self::CHANGE_ACTION_THROWS => [
                'added' => '{method} on `{uri}` now returns a `{http_code}` with a `{representation}` ' .
                    'representation: {description}',
                'removed' => '{method} on `{uri}` longer will return a `{http_code}` with a `{representation}` ' .
                    'representation: {description}'
            ],
            self::CHANGE_CONTENT_TYPE => [
                'changed' => '{method} on `{uri}` now returns a `{content_type}` Content-Type header.'
            ],
            self::CHANGE_REPRESENTATION_DATA => [
                'added' => '`{field}` has been added to the `{representation}` representation.',
                'removed' => '`{field}` has been removed from the `{representation}` representation.'
            ]
        ]
    ];

    /**
     * Take compiled API documentation and generate a JSON-encoded changelog over the life of the API.
     *
     * @return string
     */
    public function generate()
    {
        $this->template_engine = new Engine;
        $json = [];

        $changelog = parent::generate();
        foreach ($changelog as $version => $version_changes) {
            foreach ($version_changes as $change_type => $data) {
                foreach ($data as $section => $section_changes) {
                    foreach ($section_changes as $header => $changes) {
                        foreach ($changes as $identifier => $changesets) {
                            $entry = false;

                            if ($change_type === 'added') {
                                $entry = $this->getEntryForAddedChange($header, $identifier, $changesets);
                            } elseif ($change_type === 'changed') {
                                $entry = $this->getEntryForChangedItem($header, $identifier, $changesets);
                            } else {
                                $entry = $this->getEntryForRemovedItem($header, $identifier, $changesets);
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
     * @param string $header
     * @param string $identifier
     * @param array $changesets
     * @return bool|string|array
     */
    private function getEntryForAddedChange($header, $identifier, array $changesets)
    {
        if (count($changesets) > 1) {
            switch ($identifier) {
                case self::CHANGE_ACTION:
                    $methods = [];
                    foreach ($changesets as $change) {
                        $methods[] = sprintf('`%s`', $change['method']);
                    }

                    $template = $this->changeset_templates['plural'][$identifier]['added'];
                    return [
                        [
                            $this->template_engine->render($template, [
                                'uri' => $header
                            ]),
                            $methods
                        ]
                    ];
                    break;

                case self::CHANGE_ACTION_PARAM:
                    $methods = [];
                    foreach ($changesets as $change) {
                        $methods[$change['method']][] = sprintf('`%s`', $change['parameter']);
                    }

                    $entry = [];
                    foreach ($methods as $method => $params) {
                        if (count($params) > 1) {
                            $template = $this->changeset_templates['plural'][$identifier]['added'];
                            $entry[] = [
                                $this->template_engine->render($template, [
                                    'method' => $method,
                                    'uri' => $header
                                ]),
                                $params
                            ];

                            continue;
                        }

                        $template = $this->changeset_templates['singular'][$identifier]['added'];
                        $entry[] = $this->template_engine->render($template, [
                            'parameter' => rtrim(ltrim(array_shift($params), '`'), '`'),
                            'method' => $method,
                            'uri' => $header
                        ]);
                    }

                    return $entry;
                    break;

                case self::CHANGE_ACTION_RETURN:
                case self::CHANGE_ACTION_THROWS:
                    throw new \Exception('No support yet for multiple ADDED `' . $identifier . '`` in a changelog.');
                    break;

                case self::CHANGE_REPRESENTATION_DATA:
                    $fields = [];
                    foreach ($changesets as $change) {
                        $fields[] = sprintf('`%s`', $change['field']);
                    }

                    $template = $this->changeset_templates['plural'][$identifier]['added'];
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
            case self::CHANGE_ACTION:
            case self::CHANGE_ACTION_PARAM:
            case self::CHANGE_ACTION_THROWS:
            case self::CHANGE_REPRESENTATION_DATA:
                $template = $this->changeset_templates['singular'][$identifier]['added'];
                return $this->template_engine->render($template, $changeset);
                break;

            case self::CHANGE_ACTION_RETURN:
                if ($changeset['representation']) {
                    $template = $this->changeset_templates['singular'][self::CHANGE_ACTION_RETURN]['added'];
                } else {
                    $template_key = self::CHANGE_ACTION_RETURN . '_no_representation';
                    $template = $this->changeset_templates['singular'][$template_key]['added'];
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
            case self::CHANGE_CONTENT_TYPE:
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

    /**
     * Get a changelog entry for a changeset that was removed from the API.
     *
     * @param string $header
     * @param string $identifier
     * @param array $changesets
     * @return bool|string
     */
    private function getEntryForRemovedItem($header, $identifier, array $changesets)
    {
        if (count($changesets) > 1) {
            throw new \Exception('No support yet for multiple REMOVED `' . $identifier . '`` in a changelog.');
            exit;
        }

        $changeset = array_shift($changesets);
        switch ($identifier) {
            case self::CHANGE_REPRESENTATION_DATA:
            case self::CHANGE_ACTION_PARAM:
            case self::CHANGE_ACTION_RETURN:
            case self::CHANGE_ACTION_THROWS:
                $template = $this->changeset_templates['singular'][$identifier]['removed'];
                return $this->template_engine->render($template, $changeset);
                break;
        }

        return false;
    }
}
