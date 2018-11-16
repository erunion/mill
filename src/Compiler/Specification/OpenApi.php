<?php
namespace Mill\Compiler\Specification;

use Cocur\Slugify\Slugify;
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
use Mill\Parser\Annotations\VendorTagAnnotation;
use Mill\Parser\Representation;
use Mill\Parser\Representation\Documentation;
use Mill\Parser\Resource\Action;
use Symfony\Component\Yaml\Yaml;

class OpenApi extends Compiler\Specification
{
    /** @var string|null */
    protected $environment = null;

    /** @var string */
    protected $current_content_type;

    /** @var string */
    protected $current_version;

    /** @var array */
    protected $transposed_actions = [];

    /** @var array */
    protected $transposed_representations = [];

    /**
     * Take compiled API documentation and create a OpenAPI specification.
     *
     * @psalm-suppress PossiblyFalseOperand
     * @psalm-suppress InvalidScalarArgument
     * @psalm-suppress PossiblyUndefinedVariable
     * @psalm-suppress PossiblyUndefinedArrayOffset
     * @throws \Exception
     */
    public function compile(): void
    {
        parent::compile();

        $group_excludes = $this->config->getCompilerGroupExclusions();
        $resources = $this->getResources();

        foreach ($resources as $version => $groups) {
            $specification = [
                'openapi' => '3.0.0',
                'info' => [
                    'title' => $this->config->getName(),
                    'version' => $version,
                    'contact' => $this->processContact()
                ],
                'tags' => $this->processTags($groups, $group_excludes),
                'servers' => $this->processServers(),
                'paths' => [],
                'components' => [
                    'securitySchemes' => $this->processSecuritySchemes()
                ],
                'security' => [
                    [
                        'oauth2' => array_keys($this->config->getScopes())
                    ]
                ]
            ];

            // Process resource groups.
            /** @var array $data */
            foreach ($groups as $group => $data) {
                // If this group has been designated in the config file to be excluded, then exclude it.
                if (in_array($group, $group_excludes)) {
                    continue;
                }

                // Sort the resources so they're alphabetical.
                ksort($data['actions']);

                /** @var Action\Documentation $action */
                foreach ($data['actions'] as $identifier => $action) {
                    $path = $action->getPath();
                    $method = strtolower($action->getMethod());
                    $identifier = $path->getCleanPath();

                    $schema = $this->transposed_actions[$version][$identifier][$method];

                    $specification['paths'][$identifier][$method] = $schema;
                }
            }

            ksort($specification['paths']);

            // Process representation data structures.
            $representations = $this->getRepresentations($version);
            if (!empty($representations)) {
                /** @var Documentation $representation */
                foreach ($representations as $representation) {
                    $schema_name = $representation->getLabel();
                    $identifier = $this->getReferenceName($schema_name);

                    $schema = $this->transposed_representations[$version][$identifier];

                    $specification['components']['schemas'][$identifier] = $schema;
                }

                ksort($specification['components']['schemas']);
            }

            $this->specifications[$version] = $specification;
        }
    }

    /**
     * {{@inheritdoc}}
     */
    protected function transposeAction(
        string $version,
        string $group,
        string $identifier,
        Action\Documentation $action
    ): void {
        $this->current_version = $version;
        $this->current_content_type = $action->getContentType($version);

        $path = $action->getPath();
        $method = strtolower($action->getMethod());
        $identifier = $path->getCleanPath();
        if (!isset($specification['paths'][$identifier])) {
            $specification['paths'][$identifier] = [];
        }

        $schema = [
            'deprecated' => $path->isDeprecated(),
            'summary' => $action->getLabel(),
            'description' => $action->getDescription(),
            'operationId' => $action->getOperationId(),
            'tags' => [
                $group
            ],
            'parameters' => $this->processParameters($action),
            'requestBody' => $this->processRequest($action),
            'responses' => $this->processResponses($action),
            'security' => $this->processSecurity($action)
        ];

        $schema += $this->processExtensions($action, $path);

        foreach ([
            'deprecated',
            'description',
            'parameters',
            'requestBody',
            'security',
            'x-mill-path-aliased',
            'x-mill-path-aliases',
            'x-mill-vendor-tags',
            'x-mill-visibility-private'
        ] as $key) {
            if (empty($schema[$key])) {
                unset($schema[$key]);
            }
        }

        $this->transposed_actions[$version][$identifier][$method] = $schema;
    }

    /**
     * {{@inheritdoc}}
     */
    protected function transposeRepresentation(string $version, Representation\Documentation $representation): void
    {
        $fields = $representation->getExplodedContentDotNotation();
        if (empty($fields)) {
            return;
        }

        $properties = $this->processDataModel(
            $version,
            DataAnnotation::PAYLOAD_FORMAT,
            [
                'properties' => $fields
            ]
        );

        $schema_name = $representation->getLabel();
        $identifier = $this->getReferenceName($schema_name);

        $schema = [
            'title' => $schema_name
        ];

        $schema += $properties['properties'];

        $this->transposed_representations[$version][$identifier] = $schema;
    }

    /**
     * @return array
     */
    protected function processContact(): array
    {
        $contact = $this->config->getContactInformation();
        $spec = [];

        foreach (['name', 'email'] as $data) {
            if (isset($contact[$data])) {
                $spec[$data] = $contact[$data];
            }
        }

        $spec['url'] = $contact['url'];
        return $spec;
    }

    /**
     * @param array $groups
     * @param array $group_excludes
     * @return array
     */
    protected function processTags(array $groups, array $group_excludes): array
    {
        $tags = array_filter(
            array_map(
                function (string $group) use ($group_excludes): ?array {
                    if (in_array($group, $group_excludes)) {
                        return [];
                    }

                    return [
                        'name' => $group
                    ];
                },
                array_keys($groups)
            )
        );

        // Excluding some groups and filtering off empty arrays will leave gaps in the keys of the tags array,
        // resulting in some funky looking compiled YAML.
        sort($tags);

        return $tags;
    }

    /**
     * @return array
     */
    protected function processServers(): array
    {
        $spec = [];
        foreach ($this->config->getServers() as $server) {
            if (!empty($this->environment)) {
                if ($server['environment'] !== $this->environment) {
                    continue;
                }
            }

            $spec[] = [
                'url' => $server['url'],
                'description' => $server['description']
            ];
        }

        return $spec;
    }

    /**
     * @return array
     */
    protected function processSecuritySchemes(): array
    {
        $spec = [];
        $flows = $this->config->getAuthenticationFlows();

        if (isset($flows['bearer'])) {
            $spec['bearer'] = [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => $flows['bearer']['format']
            ];
        }

        if (isset($flows['oauth2']) && !empty($flows['oauth2'])) {
            $spec['oauth2'] = [
                'type' => 'oauth2',
                'flows' => (function () use ($flows): array {
                    $spec = [];
                    $scopes = [];
                    foreach ($this->config->getScopes() as $scope => $data) {
                        $scopes[$scope] = $data['description'];
                    }

                    if (isset($flows['oauth2']['authorization_code'])) {
                        $spec['authorizationCode'] = [
                            'authorizationUrl' => $flows['oauth2']['authorization_code']['authorization_url'],
                            'tokenUrl' => $flows['oauth2']['authorization_code']['token_url'],
                            'scopes' => $scopes
                        ];
                    }

                    if (isset($flows['oauth2']['client_credentials'])) {
                        $spec['clientCredentials'] = [
                            'tokenUrl' => $flows['oauth2']['client_credentials']['token_url'],
                            'scopes' => $scopes
                        ];
                    }

                    return $spec;
                })()
            ];
        }

        return $spec;
    }

    /**
     * @param Action\Documentation $action
     * @return array
     */
    protected function processParameters(Action\Documentation $action): array
    {
        return array_merge(
            $this->processDataModel(
                $this->current_version,
                PathParamAnnotation::PAYLOAD_FORMAT,
                $action->getExplodedPathParameterDotNotation()
            ),
            $this->processDataModel(
                $this->current_version,
                QueryParamAnnotation::PAYLOAD_FORMAT,
                $action->getExplodedQueryParameterDotNotation()
            )
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
                array_reduce(
                    $action->getParameters(),
                    function (?array $carry, ParamAnnotation $param): ?array {
                        if ($param->isRequired()) {
                            $carry[] = $param->getField();
                        }

                        return $carry;
                    }
                )
            ),
            'content' => [
                $this->current_content_type => [
                    'schema' => (function () use ($params): array {
                        $spec = [
                            'type' => 'object',
                            'properties' => $this->processDataModel(
                                $this->current_version,
                                ParamAnnotation::PAYLOAD_FORMAT,
                                $params
                            )
                        ];

                        $spec = $this->extractRequiredFields($spec);

                        return $spec;
                    })()
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
            if (!isset($coded_responses[$http_code])) {
                $coded_responses[$http_code] = [
                    'descriptions' => [],
                    'responses' => []
                ];
            }

            /** @var string $description */
            $description = $response->getDescription();
            if ($response instanceof ErrorAnnotation) {
                $error_code = $response->getErrorCode();
                if ($error_code) {
                    $description .= sprintf(' Returns a unique error code of `%s`.', $error_code);
                }
            }

            $coded_responses[$http_code]['descriptions'][] = $description;
            $coded_responses[$http_code]['responses'][] = $response;
        }

        foreach ($coded_responses as $http_code => $data) {
            $responses = $data['responses'];
            $total_responses = count($responses);

            // OpenAPI doesn't have support for multiple responses of the same HTTP code, so let's mash them down
            // together, but document to the developer what's going on.
            if ($total_responses > 1) {
                $description = sprintf(
                    'There are %s ways that this status code can be encountered:',
                    (new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format($total_responses)
                );

                $description .= $this->line();
                $description .= implode(
                    $this->line(),
                    array_map(function (string $desc): string {
                        return sprintf(' * %s', $desc);
                    }, $data['descriptions'])
                );
            } else {
                /** @var string $description */
                $description = current($data['descriptions']);
            }

            $spec = [
                'description' => trim($description) ?: 'Standard request.'
            ];

            /** @var ReturnAnnotation|ErrorAnnotation $response */
            $response = array_shift($responses);
            $representation = $response->getRepresentation();
            $representations = $this->getRepresentations($this->current_version);

            if ($representation && isset($representations[$representation])) {
                /** @var Documentation $docs */
                $docs = $representations[$representation];
                $fields = $docs->getRawContent();
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
                        $this->current_content_type => [
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
                'oauth2' => array_map(function (ScopeAnnotation $scope): string {
                    return $scope->getScope();
                }, $scopes)
            ]
        ];
    }

    /**
     * @param Action\Documentation $action
     * @param PathAnnotation $path
     * @return array
     */
    protected function processExtensions(Action\Documentation $action, PathAnnotation $path): array
    {
        $schema = [
            'x-mill-path-aliased' => $path->isAliased(),
            'x-mill-path-aliases' => array_map(
                function (PathAnnotation $alias): string {
                    return $alias->getCleanPath();
                },
                $path->getAliases()
            ),
            'x-mill-path-template' => $path->getPath(),
            'x-mill-vendor-tags' => $this->processVendorTags($action),
            'x-mill-visibility-private' => !$path->isVisible()
        ];

        return $schema;
    }

    /**
     * @param Action\Documentation $action
     * @return array
     */
    protected function processVendorTags(Action\Documentation $action): array
    {
        $vendor_tags = $action->getVendorTags();
        if (empty($vendor_tags)) {
            return [];
        }

        return array_map(function (VendorTagAnnotation $vendor_tag): string {
            return $vendor_tag->getVendorTag();
        }, $vendor_tags);
    }

    /**
     * @param string $version
     * @param string $payload_format
     * @param array $fields
     * @return array
     */
    private function processDataModel(string $version, string $payload_format, array $fields = []): array
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
                        '`' . implode('`, `', array_map(function (array $scope): string {
                            return $scope['scope'];
                        }, $data['scopes'])) . '`',
                        (count($data['scopes']) > 1) ? 's' : null
                    );
                }

                if ($data['sample_data'] !== false) {
                    $spec['schema']['example'] = $this->convertSampleDataToCompatibleDataType(
                        $data['sample_data'],
                        $spec['schema']['type']
                    );
                }

                if (array_key_exists('nullable', $data) && $data['nullable']) {
                    $spec['schema']['nullable'] = true;
                }

                if ($spec['schema']['type'] === 'object') {
                    $representation = $this->getRepresentation($data['type'], $version);
                    if ($representation) {
                        $ref = '#/components/schemas/' . $this->getReferenceName($representation->getLabel());

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

                if (isset($data['vendor_tags']) && !empty($data['vendor_tags'])) {
                    $spec['x-mill-vendor-tags'] = $data['vendor_tags'];
                }
            } else {
                $spec = [
                    'name' => $field_name,
                    'in' => $payload_format,
                    'schema' => [
                        'type' => 'object'
                    ]
                ];
            }

            // If we're processing MSON for a component, clean it up so it can be used as a component.
            if (in_array($payload_format, [DataAnnotation::PAYLOAD_FORMAT, ParamAnnotation::PAYLOAD_FORMAT])) {
                if (isset($spec['schema'])) {
                    $spec += $spec['schema'];
                    unset($spec['schema']);
                }

                unset($spec['name']);
                unset($spec['in']);
            }

            // Process any exploded dot notation children of this field.
            unset($field[Application::DOT_NOTATION_ANNOTATION_DATA_KEY]);
            if (!empty($field)) {
                if (empty($data)) {
                    $spec['properties'] = $this->processDataModel($version, $payload_format, $field);
                } elseif ($data['type'] === 'array' && $data['subtype'] === 'object') {
                    $spec['items'] = [
                        'type' => 'object',
                        'properties' => $this->processDataModel($version, $payload_format, $field)
                    ];
                } elseif ($data['type'] === 'object') {
                    $spec['properties'] = $this->processDataModel($version, $payload_format, $field);
                } else {
                    $spec['items'] = $this->processDataModel($version, $payload_format, $field);
                }
            } elseif ($data['type'] === 'array') {
                if (!empty($data['subtype'])) {
                    $representation = $this->getRepresentation($data['subtype'], $version);
                    if ($representation) {
                        $ref = '#/components/schemas/' . $this->getReferenceName($representation->getLabel());

                        $spec['items']['$ref'] = $ref;
                    } else {
                        $spec['items'] = [
                            'type' => 'string'
                        ];
                    }
                } else {
                    $spec['items'] = [
                        'type' => 'string'
                    ];
                }
            }

            // Request body and response schema requirement definitions need to be separate from the item schema.
            if (in_array($payload_format, [DataAnnotation::PAYLOAD_FORMAT, ParamAnnotation::PAYLOAD_FORMAT])) {
                $spec = $this->extractRequiredFields($spec);
            }

            ksort($spec);

            // Path and query parameters should not be keyed off the field name.
            if (in_array($payload_format, [
                PathParamAnnotation::PAYLOAD_FORMAT,
                QueryParamAnnotation::PAYLOAD_FORMAT
            ])) {
                // Fix up any quirks left behind on processing these data models because we can't have a `schema`
                // element present with `items` and/or `properties`.
                if (isset($spec['items'])) {
                    $spec['schema']['items'] = $spec['items'];
                    unset($spec['items']);
                } elseif (isset($spec['properties'])) {
                    $spec['schema']['properties'] = $spec['properties'];
                    unset($spec['properties']);
                }

                $schema[] = $spec;
            } else {
                $schema[$field_name] = $spec;
            }
        }

        return $schema;
    }

    /**
     * @param array $spec
     * @return array
     */
    private function extractRequiredFields(array $spec): array
    {
        /** @var array $properties */
        $properties = [];
        if (isset($spec['properties'])) {
            $properties = $spec['properties'];
        } elseif (isset($spec['items']['properties'])) {
            $properties = $spec['items']['properties'];
        }

        if (!empty($properties)) {
            $required = [];
            foreach ($properties as $name => $property) {
                if (!array_key_exists('required', $property)) {
                    continue;
                } elseif ($property['required']) {
                    $required[] = $name;

                    // If the required property at this level is an array, then it contains nested properties that are
                    // required for this current property. Don't remove it.
                    if (is_array($property['required'])) {
                        continue;
                    }
                }

                unset($properties[$name]['required']);
            }

            if (isset($spec['properties'])) {
                $spec['properties'] = $properties;
                if (!empty($required)) {
                    $spec['required'] = $required;
                }
            } elseif (isset($spec['items']['properties'])) {
                $spec['items']['properties'] = $properties;
                if (!empty($required)) {
                    $spec['items']['required'] = $required;
                }
            }
        }

        return $spec;
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
            case 'array':
            case 'boolean':
            case 'number':
            case 'string':
                return $type;
                break;

            case 'float':
            case 'integer':
                return 'number';
                break;

            case 'date':
            case 'datetime':
            case 'enum':
            case 'timestamp':
            case 'uri':
                return 'string';
                break;

            default:
                return 'object';
        }

        return $type;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getReferenceName(string $name): string
    {
        return (new Slugify())->slugify($name);
    }

    /**
     * @param string $environment
     * @return OpenApi
     */
    public function setEnvironment(string $environment): self
    {
        $this->environment = strtolower($environment);
        return $this;
    }

    /**
     * @param array $specification
     * @return string
     */
    public static function getYaml(array $specification): string
    {
        return Yaml::dump($specification, PHP_INT_MAX, 2);
    }
}
