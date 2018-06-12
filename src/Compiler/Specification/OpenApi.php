<?php
namespace Mill\Compiler\Specification;

use Mill\Application;
use Mill\Compiler;
use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Annotations\ErrorAnnotation;
use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Annotations\PathAnnotation;
use Mill\Parser\Annotations\PathParamAnnotation;
use Mill\Parser\Annotations\QueryParamAnnotation;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Annotations\ScopeAnnotation;
use Mill\Parser\Representation\Documentation;
use Mill\Parser\Resource\Action;

class OpenApi extends Compiler\Specification
{
    use Compiler\Traits\Markdown;

    /**
     * Take compiled API documentation and create a API Blueprint specification.
     *
     * @psalm-suppress PossiblyFalseOperand
     * @psalm-suppress InvalidScalarArgument
     * @psalm-suppress PossiblyUndefinedVariable
     * @psalm-suppress PossiblyUndefinedArrayOffset
     * @return array
     * @throws \Exception
     */
    public function compile(): array
    {
        parent::compile();

        $group_excludes = $this->config->getCompilerGroupExclusions();
        $resources = $this->getResources();

        $specifications = [];

        /** @var array $data */
        foreach ($resources as $version => $groups) {
            $this->version = $version;
            $this->representations = $this->getRepresentations($this->version);

            $specifications[$this->version] = [
                'openapi' => '3.0.0',
                'info' => [
                    'title' => $this->config->getName(),
                    'version' => $this->version
                ],
                'tags' => (function () use ($groups, $group_excludes) {
                    $tags = array_filter(array_map(function ($group) use ($group_excludes) {
                        if (!in_array($group, $group_excludes)) {
                            return [
                                'name' => $group
                            ];
                        }
                    }, array_keys($groups)));

                    // Excluding some groups and filtering off empty arrays will leave gaps in the keys of the tags
                    // array, resulting in some funky looking compiled YAML.
                    sort($tags);

                    return $tags;
                })(),
                /*'servers' => [
                    ['url' => '']
                ],*/
                'paths' => [],
                'components' => [],
                'security' => [
                    [
                        'OAuth2' => $this->config->getScopes()
                    ]
                ]
            ];

            // Process resource groups.
            foreach ($groups as $group => $data) {
                // If this group has been designated in the config file to be excluded, then exclude it.
                if (in_array($group, $group_excludes)) {
                    continue;
                }

                // Sort the resources so they're alphabetical.
                ksort($data['resources']);

                /** @var array $resource */
                foreach ($data['resources'] as $identifier => $resource) {
                    /** @var Action\Documentation $action */
                    foreach ($resource['actions'] as $action) {
                        $method = strtolower($action->getMethod());
                        $identifier = $action->getPath()->getCleanPath();
                        if (!isset($specifications[$this->version]['paths'][$identifier])) {
                            $specifications[$this->version]['paths'][$identifier] = [];
                        }

                        $spec = [
                            'summary' => $action->getLabel(),
                            'description' => $action->getDescription(),
                            'operationId' => $this->transformActionIntoOperationId($action),
                            'tags' => [
                                $group
                            ],
                            'parameters' => $this->processParameters($action),
                            'requestBody' => $this->processRequest($action),
                            'responses' => $this->processResponses($action),
                            'security' => $this->processSecurity($action)
                        ];

                        foreach (['parameters', 'requestBody', 'security'] as $key) {
                            if (empty($spec[$key])) {
                                unset($spec[$key]);
                            }
                        }

                        $specifications[$this->version]['paths'][$identifier][$method] = $spec;
                    }
                }
            }

            // Process representation data structures.
            if (!empty($this->representations)) {
                foreach ($this->representations as $representation) {
                    $fields = $representation->getExplodedContentDotNotation();
                    if (empty($fields)) {
                        continue;
                    }

                    $identifier = $this->getReferenceName($representation->getLabel());

                    /*$contents = sprintf('## %s', $identifier);
                    $contents .= $this->line();

                    $contents .= $this->processMSON($fields, 0);

                    $contents = trim($contents);
                    $specifications[$this->version]['structures'][$identifier] = $contents;*/

                    $specifications[$this->version]['components']['schemas'][$identifier] = [
                        'properties' => $this->processMSON(DataAnnotation::PAYLOAD_FORMAT, $fields)
                    ];
                }
            }

            // Process the combined file.
            /*$specifications[$this->version]['combined'] = $this->processCombinedFile(
                $specifications[$this->version]['groups'],
                $specifications[$this->version]['structures']
            );*/
        }

        return $specifications;
    }

    /**
     * @param Action\Documentation $action
     * @return array
     */
    protected function processParameters(Action\Documentation $action): array
    {
        return array_merge(
            $this->processMSON(PathParamAnnotation::PAYLOAD_FORMAT, $action->getExplodedPathParameterDotNotation()),
            $this->processMSON(QueryParamAnnotation::PAYLOAD_FORMAT, $action->getExplodedQueryParameterDotNotation())
        );
    }

    /**
     * @param Action\Documentation $action
     * @return array
     * @throws \Exception
     */
    protected function processRequest(Action\Documentation $action): array
    {
        $params = $action->getExplodedParameterDotNotation();
        if (empty($params)) {
            return [];
        }

        return [
            'required' => !empty(
                array_reduce($action->getParameters(), function ($carry, ParamAnnotation $param): ?array {
                    if ($param->isRequired()) {
                        $carry[] = $param->getField();
                    }

                    return $carry;
                })
            ),
            'content' => [
                $action->getContentType($this->version) => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => $this->processMSON(ParamAnnotation::PAYLOAD_FORMAT, $params)
                    ]
                ]
            ]
        ];
    }

    /**
     * @param Action\Documentation $action
     * @return array
     * @throws \Exception
     */
    protected function processResponses(Action\Documentation $action): array
    {
        $schema = [];
        $coded_responses = [];

        /** @var ReturnAnnotation|ErrorAnnotation $response */
        foreach ($action->getResponses() as $response) {
            $http_code = substr($response->getHttpCode(), 0, 3);
            $coded_responses[$http_code][] = $response;
        }

        foreach ($coded_responses as $http_code => $responses) {
            $total_responses = count($responses);

            // OpenAPI doesn't have support for multiple responses of the same HTTP code, so let's mash them down
            // together, but document to the developer what's going on.
            if ($total_responses > 1) {
                $description = sprintf(
                    'There are %s ways that this status code can be encountered:',
                    (new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format(count($responses))
                );

                $description .= $this->line();
            } else {
                $description = current($responses)->getDescription();
            }

            /** @var ReturnAnnotation|ErrorAnnotation $response */
            foreach ($responses as $response) {
                $response_description = $response->getDescription();
                if ($total_responses > 1) {
                    $description .= sprintf(' * %s', $response_description);
                }

                if ($response instanceof ErrorAnnotation) {
                    $error_code = $response->getErrorCode();
                    if ($error_code) {
                        $description .= sprintf(' Returns a unique error code of `%s`.', $error_code);
                    }
                }

                $description .= $this->line();
            }

            $spec = [
                'description' => trim($description) ?: 'Standard request.'
            ];

            /** @var ReturnAnnotation|ErrorAnnotation $response */
            $response = array_shift($responses);
            $representation = $response->getRepresentation();
            $representations = $this->getRepresentations($this->version);
            if (isset($representations[$representation])) {
                /** @var Documentation $docs */
                $docs = $representations[$representation];
                $fields = $docs->getExplodedContentDotNotation();
                if (!empty($fields)) {
                    $ref_name = $this->getReferenceName($docs->getLabel());
                    $response_schema = [
                        '$ref' => '#/components/schemas/' . $ref_name
                    ];

                    if ($response instanceof ReturnAnnotation && $response->getType() === 'collection') {
                        $response_schema = [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/components/schemas/' . $ref_name
                            ]
                        ];
                    }

                    $spec['content'] = [
                        $action->getContentType($this->version) => [
                            'schema' => $response_schema
                        ]
                    ];
                }
            }

            $schema[$http_code] = $spec;
        }

        return $schema;
    }

    /**
     * Recursively process an array of representation fields.
     *
     * @param array $fields
     * @param int $indent
     * @return string
     */
    /*private function processMSON(array $fields = [], int $indent = 2): string
    {
        $blueprint = '';

        /** @var array $field
        foreach ($fields as $field_name => $field) {
            $blueprint .= $this->tab($indent);

            $data = [];
            if (isset($field[Application::DOT_NOTATION_ANNOTATION_DATA_KEY])) {
                /** @var array $data
                $data = $field[Application::DOT_NOTATION_ANNOTATION_DATA_KEY];
                $type = $this->convertTypeToCompatibleType(
                    $data['type'],
                    (isset($data['subtype'])) ? $data['subtype'] : false
                );

                $sample_data = $this->convertSampleDataToCompatibleDataType($data['sample_data'], $type);

                $description = $data['description'];
                if (!empty($data['scopes'])) {
                    // If this description doesn't end with punctuation, add a period before we display a list of
                    // required authentication scopes.
                    $description .= (!in_array(substr($description, -1), ['.', '!', '?'])) ? '.' : '';

                    $strings = [];
                    foreach ($data['scopes'] as $scope) {
                        $strings[] = $scope['scope'];
                    }

                    $description .= sprintf(
                        ' This data requires a bearer token with the %s scope%s.',
                        '`' . implode(', ', $strings) . '`',
                        (count($strings) > 1) ? 's' : null
                    );
                }

                $blueprint .= sprintf(
                    '- `%s`%s (%s%s%s) - %s',
                    $field_name,
                    ($sample_data !== false) ? sprintf(': `%s`', $sample_data) : '',
                    $type,
                    (isset($data['required']) && $data['required']) ? ', required' : null,
                    ($data['nullable']) ? ', nullable' : null,
                    $description
                );

                $blueprint .= $this->line();

                // Only enum's support options/members.
                if (($data['type'] === 'enum' || (isset($data['subtype']) && $data['subtype'] === 'enum')) &&
                    !empty($data['values'])
                ) {
                    $blueprint .= $this->tab($indent + 1);
                    $blueprint .= '+ Members';
                    $blueprint .= $this->line();

                    foreach ($data['values'] as $value => $value_description) {
                        $blueprint .= $this->tab($indent + 2);
                        $blueprint .= sprintf(
                            '+ `%s`%s',
                            $value,
                            (!empty($value_description)) ? sprintf(' - %s', $value_description) : ''
                        );

                        $blueprint .= $this->line();
                    }
                }
            } else {
                $blueprint .= sprintf('- `%s` (object)', $field_name);
                $blueprint .= $this->line();
            }

            // Process any exploded dot notation children of this field.
            unset($field[Application::DOT_NOTATION_ANNOTATION_DATA_KEY]);
            if (!empty($field)) {
                // If this is an array, and has a subtype of object, we should indent a bit so we can properly render
                // out the array objects.
                if (!empty($data) && isset($data['subtype']) && $data['subtype'] === 'object') {
                    $blueprint .= $this->tab($indent + 1);
                    $blueprint .= ' - (object)';
                    $blueprint .= $this->line();

                    $blueprint .= $this->processMSON($field, $indent + 2);
                } else {
                    $blueprint .= $this->processMSON($field, $indent + 1);
                }
            }
        }

        return $blueprint;
    }*/

    /**
     * Given an array of resource groups, and representation structures, build a combined API Blueprint file.
     *
     * @param array $groups
     * @param array $structures
     * @return string
     */
    /*protected function processCombinedFile(array $groups = [], array $structures = []): string
    {
        $blueprint = 'FORMAT: 1A';
        $blueprint .= $this->line(2);

        $api_name = $this->config->getName();
        if (!empty($api_name)) {
            $blueprint .= sprintf('# %s', $api_name);
            $blueprint .= $this->line();

            $blueprint .= sprintf("This is the API Blueprint file for %s.", $api_name);
            $blueprint .= $this->line(2);
        }

        if (!empty($groups)) {
            $blueprint .= implode($this->line(2), $groups);
        }

        if (!empty($structures)) {
            if (!empty($groups)) {
                $blueprint .= $this->line(2);
            }

            ksort($structures);

            $blueprint .= '# Data Structures';
            $blueprint .= $this->line();

            $blueprint .= implode($this->line(2), $structures);
        }

        $blueprint = trim($blueprint);

        return $blueprint;
    }*/

    /**
     * @param Action\Documentation $action
     * @return array
     */
    protected function processSecurity(Action\Documentation $action): array
    {
        $scopes = $action->getScopes();
        if (empty($scopes)) {
            return [];
        }

        return [
            [
                'OAuth2' => array_map(function (ScopeAnnotation $scope): string {
                    return $scope->getScope();
                }, $scopes)
            ]
        ];
    }

    /**
     * @param Action\Documentation $action
     * @return string
     * @throws \Exception
     */
    private function transformActionIntoOperationId(Action\Documentation $action): string
    {
        $path = $action->getPath()->getCleanPath();
        $path = str_replace(['{', '}'], '', $path);
        $path = str_replace('/', ' ', $path);
        $path = ucwords($path);
        $path = str_replace(' ', '', $path);

        return strtolower($action->getMethod()) . $path;
    }

    /**
     * @param string $payload_format
     * @param array $fields
     * @return array
     */
    private function processMSON(string $payload_format, array $fields = []): array
    {
        $schema = [];

        /** @var array $field */
        foreach ($fields as $field_name => $field) {
            $data = [];
            if (isset($field[Application::DOT_NOTATION_ANNOTATION_DATA_KEY])) {
                /** @var array $data */
                $data = $field[Application::DOT_NOTATION_ANNOTATION_DATA_KEY];

                $spec = [
                    'name' => $field_name,
                    'in' => $payload_format,
                    'description' => $data['description'],
                    'required' => (array_key_exists('required', $data) && $data['required']),
                    'schema' => [
                        'type' => $this->convertTypeToCompatibleType($data['type'])
                    ]
                ];

                if (!empty($data['scopes'])) {
                    // If this description doesn't end with punctuation, add a period before we display a list of
                    // required authentication scopes.
                    $spec['description'] .= (!in_array(substr($spec['description'], -1), ['.', '!', '?'])) ? '.' : '';
                    $spec['description'] .= sprintf(
                        ' This data requires a bearer token with the %s scope%s.',
                        '`' . implode('`, `', array_map(function ($scope) {
                            return $scope['scope'];
                        }, $data['scopes'])) . '`',
                        (count($data['scopes']) > 1) ? 's' : null
                    );
                }

                if ($data['sample_data'] !== false) {
                    $spec['schema']['example'] = $this->convertSampleDataToCompatibleDataType($data['sample_data'], $spec['schema']['type']);
                }

                if (array_key_exists('nullable', $data) && $data['nullable']) {
                    $spec['schema']['nullable'] = true;
                }

                if ($spec['schema']['type'] === 'object') {
                    $representation = $this->getRepresentation($data['type']);
                    if ($representation) {
                        $ref = '#/components/schemas/' . $this->getReferenceName($representation->getLabel());;

                        if ($payload_format === DataAnnotation::PAYLOAD_FORMAT) {
                            unset($spec['schema']['type']);

                            $spec['allOf'] = [
                                [
                                    '$ref' => $ref
                                ]
                            ];
                        } else {
                            $spec['schema']['$ref'] = $ref;
                        }
                    }
                }

                // Only enum's support options/members.
                if (($data['type'] === 'enum' || (isset($data['subtype']) && $data['subtype'] === 'enum')) &&
                    !empty($data['values'])
                ) {
                    $addendum = '';
                    $spec['schema']['enum'] = [];

                    foreach ($data['values'] as $value => $value_description) {
                        $spec['schema']['enum'][] = $value;

                        if (!empty($value_description)) {
                            $addendum .= sprintf(' * `%s` - %s', $value, $value_description);
                            $addendum .= $this->line();
                        }
                    }

                    if (!empty($addendum)) {
                        $spec['description'] .= $this->line(2);
                        $spec['description'] .= 'Option descriptions:';
                        $spec['description'] .= $this->line();
                        $spec['description'] .= $addendum;
                    }
                }
            } else {
                $spec = [
                    'name' => $field_name,
                    'in' => $payload_format,
                    'schema' => [
                        'type' => 'object',
                        'properties' => []
                    ]
                ];
            }

            // If we're processing MSON for a component, clean it up so it can be used as a component.
            switch ($payload_format) {
                case DataAnnotation::PAYLOAD_FORMAT:
                    if (isset($spec['schema'])) {
                        $spec += $spec['schema'];
                        unset($spec['schema']);
                    }

                    unset($spec['name']);
                    unset($spec['in']);
                    unset($spec['required']);
                    break;

                case ParamAnnotation::PAYLOAD_FORMAT:
                    if (isset($spec['schema'])) {
                        $spec += $spec['schema'];
                        unset($spec['schema']);
                    }

                    unset($spec['name']);
                    unset($spec['in']);
                    unset($spec['required']); //@todo
                    break;
            }

            // Process any exploded dot notation children of this field.
            unset($field[Application::DOT_NOTATION_ANNOTATION_DATA_KEY]);
            if (!empty($field)) {
                if ($payload_format === DataAnnotation::PAYLOAD_FORMAT) {
                    if (empty($data)) {
                        $spec['properties'] = $this->processMSON($payload_format, $field);
                    } elseif ($data['type'] === 'array' && $data['subtype'] === 'object') {
                        $spec['items'] = [
                            'type' => 'object',
                            'properties' => $this->processMSON($payload_format, $field)
                        ];
                    } else {
                        $spec['items'] = $this->processMSON($payload_format, $field);
                    }
                } elseif (isset($data['subtype']) && $data['subtype'] === 'object') {
                    if ($payload_format === ParamAnnotation::PAYLOAD_FORMAT && $data['type'] === 'array') {
                        $spec['items'] = [
                            'type' => 'object',
                            'properties' => $this->processMSON($payload_format, $field)
                        ];
                    } else {
                        $spec['properties'] = $this->processMSON($payload_format, $field);
                    }
                } else {
                    $spec['items'] = $this->processMSON($payload_format, $field);
                }

                // If this is an array, and has a subtype of object, we should indent a bit so we can properly render
                // out the array objects.
                /*if ((!empty($data) && isset($data['subtype']) && $data['subtype'] === 'object')) {
                    if ($payload_format === DataAnnotation::PAYLOAD_FORMAT && $data['type'] === 'array') {
                        $spec['items'] = $this->processMSON($payload_format, $field);
                    } else {
                        $spec['properties'] = $this->processMSON($payload_format, $field);
                    }
                } else {
                    $spec['items'] = $this->processMSON($payload_format, $field);
                }*/
            } elseif ($data['type'] === 'array') {
                $spec['items'] = [
                    'type' => 'string'
                ];
            }

            if (in_array($payload_format, [PathParamAnnotation::PAYLOAD_FORMAT, QueryParamAnnotation::PAYLOAD_FORMAT])) {
                $schema[] = $spec;
            } else {
                $schema[$field_name] = $spec;
            }
        }

        return $schema;
    }

    /**
     * Convert a Mill-supported documentation into an OpenAPI-compatible type.
     *
     * @link https://swagger.io/docs/specification/data-models/data-types/
     * @param string $type
     * @return string
     */
    private function convertTypeToCompatibleType(string $type): string
    {
        switch ($type) {
            case 'enum':
                return 'string';
                break;

            case 'float':
            case 'integer':
                return 'number';
                break;

            case 'date':
            case 'datetime':
            case 'timestamp':
            case 'uri':
                return 'string';
                break;

            case 'array':
                return 'array';
                break;

            default:
                return 'object';
                break;
        }

        return $type;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getReferenceName(string $name): string
    {
        return str_replace(' ', '', ucwords($name));
    }
}
