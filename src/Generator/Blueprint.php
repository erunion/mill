<?php
namespace Mill\Generator;

use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Generator;
use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Annotations\ScopeAnnotation;
use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Annotations\UriSegmentAnnotation;
use Mill\Parser\Representation\Documentation;
use Mill\Parser\Resource\Action;

class Blueprint extends Generator
{
    use Generator\Traits\Markdown;

    /**
     * Current list of representations for the current API version we're working with.
     *
     * @var array
     */
    private $representations = [];

    /**
     * Take compiled API documentation and generate API Blueprint representations.
     *
     * @psalm-suppress PossiblyFalseOperand
     * @return array
     */
    public function generate(): array
    {
        parent::generate();

        $namespace_excludes = $this->config->getBlueprintNamespaceExcludes();
        $resources = $this->getResources();

        $blueprints = [];

        /** @var array $data */
        foreach ($resources as $version => $namespaces) {
            $this->version = $version;
            $this->representations = $this->getRepresentations($this->version);

            // Process resource groups.
            foreach ($namespaces as $namespace => $data) {
                // If this namespace has been designated in the config file to be excluded, then exclude it.
                if (in_array($namespace, $namespace_excludes)) {
                    continue;
                }

                $contents = sprintf('# Group %s', $namespace);
                $contents .= $this->line();

                // Sort the resources so they're alphabetical.
                ksort($data['resources']);

                $resource_contents = [];

                /** @var array $resource */
                foreach ($data['resources'] as $identifier => $resource) {
                    /** @var Action\Documentation $action */
                    foreach ($resource['actions'] as $action) {
                        $resource_key = sprintf('%s [%s]', $identifier, $action->getUri()->getCleanPath());
                        if (!isset($resource_contents[$resource_key])) {
                            $resource_contents[$resource_key] = [
                                'description' => $resource['description'],
                                'actions' => []
                            ];
                        }

                        $action_contents = '';

                        $description = $action->getDescription();
                        if (!empty($description)) {
                            $action_contents .= $description;
                            $action_contents .= $this->line(2);
                        }

                        $action_contents .= $this->processScopes($action);
                        $action_contents .= $this->processParameters($action);
                        $action_contents .= $this->processRequest($action);

                        $coded_responses = [];
                        /** @var ReturnAnnotation|ThrowsAnnotation $response */
                        foreach ($action->getResponses() as $response) {
                            $coded_responses[$response->getHttpCode()][] = $response;
                        }

                        // Keep things tidy.
                        ksort($coded_responses);

                        foreach ($coded_responses as $http_code => $responses) {
                            $action_contents .= $this->processResponses($action, $http_code, $responses);
                        }

                        $action_key = sprintf('%s [%s]', $action->getLabel(), $action->getMethod());
                        $resource_contents[$resource_key]['actions'][$action_key] = $action_contents;
                    }
                }

                // Since there are instances where the same resource might be used with multiple endpoints, and on the
                // same group, we need to abstract out the resource and action concatenation so we can generate unique
                // resource and action headers for each resource action.
                //
                // It would be nice to clean this code up at some po as it's a... bit... messy.
                foreach ($resource_contents as $identifier => $resource) {
                    $contents .= sprintf('## %s', $identifier);
                    $contents .= $this->line();

                    if (!is_null($resource['description'])) {
                        $contents .= $resource['description'];
                        $contents .= $this->line();
                    }

                    foreach ($resource['actions'] as $action_identifier => $markdown) {
                        $contents .= $this->line();
                        $contents .= sprintf('### %s', $action_identifier);
                        $contents .= $this->line();

                        $contents .= $markdown;
                    }

                    $contents .= $this->line();
                }

                $contents = trim($contents);
                $blueprints[$this->version]['groups'][$namespace] = $contents;
            }

            // Process representation data structures.
            if (!empty($this->representations)) {
                foreach ($this->representations as $representation) {
                    $fields = $representation->getExplodedContentDotNotation();
                    if (empty($fields)) {
                        continue;
                    }

                    $identifier = $representation->getLabel();

                    $contents = sprintf('## %s', $identifier);
                    $contents .= $this->line();

                    $contents .= $this->processRepresentationFields($fields, 0);

                    $contents = trim($contents);
                    $blueprints[$this->version]['structures'][$identifier] = $contents;
                }
            }

            // Process the combined file.
            $blueprints[$this->version]['combined'] = $this->processCombinedFile(
                $blueprints[$this->version]['groups'],
                $blueprints[$this->version]['structures']
            );
        }

        return $blueprints;
    }

    /**
     * Process an action and generate a scopes description.
     *
     * @param Action\Documentation $action
     * @return false|string
     */
    protected function processScopes(Action\Documentation $action)
    {
        $scopes = $action->getScopes();
        if (empty($scopes)) {
            return false;
        }

        $strings = [];
        /** @var ScopeAnnotation $scope */
        foreach ($scopes as $scope) {
            $strings[] = $scope->getScope();
        }

        $blueprint = sprintf(
            'This action requires a bearer token with the %s scope%s.',
            '`' . implode(', ', $strings) . '`',
            (count($strings) > 1) ? 's' : null
        );

        $blueprint .= $this->line(2);

        return $blueprint;
    }

    /**
     * Process an action and generate a parameters Blueprint.
     *
     * @param Action\Documentation $action
     * @return false|string
     */
    protected function processParameters(Action\Documentation $action)
    {
        $segments = $action->getUriSegments();
        if (empty($segments)) {
            return false;
        }

        $blueprint = '+ Parameters';
        $blueprint .= $this->line();

        $translations = $this->config->getUriSegmentTranslations();

        /** @var UriSegmentAnnotation $segment */
        foreach ($segments as $segment) {
            $field = $segment->getField();
            foreach ($translations as $from => $to) {
                $field = str_replace($from, $to, $field);
            }

            /** @var array $values */
            $values = $segment->getValues();
            $type = $this->convertTypeToCompatibleType($segment->getType());

            $blueprint .= $this->tab();
            $blueprint .= sprintf(
                '- `%s` (%s, required) - %s',
                $field,
                $type,
                $segment->getDescription()
            );

            $blueprint .= $this->line();

            if (!empty($values)) {
                $blueprint .= $this->tab(2);
                $blueprint .= '+ Members';
                $blueprint .= $this->line();

                foreach ($values as $value => $value_description) {
                    $blueprint .= $this->tab(3);
                    $blueprint .= sprintf(
                        '+ `%s`%s',
                        $value,
                        (!empty($value_description)) ? sprintf(' - %s', $value_description) : ''
                    );

                    $blueprint .= $this->line();
                }
            }
        }

        return $blueprint;
    }

    /**
     * Process an action and generate a Request Blueprint.
     *
     * @param Action\Documentation $action
     * @return string
     */
    protected function processRequest(Action\Documentation $action): string
    {
        $params = $action->getParameters();
        if (empty($params)) {
            return '';
        }

        $blueprint = '+ Request';
        $blueprint .= $this->line();

        // Build up request attributes.
        $blueprint .= $this->tab();
        $blueprint .= '+ Attributes';
        $blueprint .= $this->line();

        /** @var ParamAnnotation $param */
        foreach ($params as $param) {
            $values = $param->getValues();
            $type = $this->convertTypeToCompatibleType($param->getType());
            $sample_data = $this->convertSampleDataToCompatibleDataType($param->getSampleData(), $type);

            $blueprint .= $this->tab(2);
            $blueprint .= sprintf(
                '- `%s`%s (%s%s%s) - %s',
                $param->getField(),
                ($sample_data !== false) ? sprintf(': `%s`', $sample_data) : '',
                (!empty($values) && $param->getType() !== 'enum') ? 'enum[' . $type . ']' : $type,
                ($param->isRequired()) ? ', required' : null,
                ($param->isNullable()) ? ', nullable' : null,
                $param->getDescription()
            );

            $blueprint .= $this->line();

            if (!empty($values)) {
                $blueprint .= $this->tab(3);
                $blueprint .= '+ Members';
                $blueprint .= $this->line();

                foreach ($values as $value => $value_description) {
                    $blueprint .= $this->tab(4);
                    $blueprint .= sprintf(
                        '+ `%s`%s',
                        $value,
                        (!empty($value_description)) ? sprintf(' - %s', $value_description) : ''
                    );

                    $blueprint .= $this->line();
                }
            }
        }

        return $blueprint;
    }

    /**
     * Process an action and response array and generate a Response Blueprint.
     *
     * @param Action\Documentation $action
     * @param string $http_code
     * @param array $responses
     * @return string
     * @throws \Exception If a non-200 response is missing a description.
     * @throws NoAnnotationsException If a used representation does not have any documentation.
     */
    protected function processResponses(Action\Documentation $action, string $http_code, array $responses = []): string
    {
        $http_code = substr($http_code, 0, 3);

        $blueprint = '+ Response ' . $http_code . ' (' . $action->getContentType($this->version) . ')';
        $blueprint .= $this->line();

        $multiple_responses = count($responses) > 1;

        // API Blueprint doesn't have support for multiple responses of the same HTTP code, so let's mash them down
        // together, but document to the developer what's going on.
        if ($multiple_responses) {
            // @todo Blueprint validation doesn't seem to like 200 responses with descriptions. Just skip for now.
            if (!in_array($http_code, [201, 204])) {
                $response_count = (new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format(count($responses));

                $blueprint .= $this->tab();
                $blueprint .= sprintf('There are %s ways that this status code can be encountered:', $response_count);
                $blueprint .= $this->line();

                /** @var ReturnAnnotation|ThrowsAnnotation $response */
                foreach ($responses as $response) {
                    $description = $response->getDescription();
                    $description = (!empty($description)) ? $description : 'Standard request.';
                    $blueprint .= $this->tab(2);
                    $blueprint .= sprintf(' * %s', $description);
                    if ($response instanceof ThrowsAnnotation) {
                        $error_code = $response->getErrorCode();
                        if ($error_code) {
                            $blueprint .= sprintf(' Unique error code: %s', $error_code);
                        }
                    }

                    $blueprint .= $this->line();
                }
            }
        }

        /** @var ReturnAnnotation|ThrowsAnnotation $response */
        $response = array_shift($responses);
        $representation = $response->getRepresentation();
        $representations = $this->getRepresentations($this->version);
        if (!isset($representations[$representation])) {
            return $blueprint;
        }

        /** @var \Mill\Parser\Representation\Documentation $docs */
        $docs = $representations[$representation];
        $fields = $docs->getExplodedContentDotNotation();
        if (!empty($fields)) {
            $blueprint .= $this->tab();

            $attribute_type = $docs->getLabel();
            if ($response instanceof ReturnAnnotation) {
                if ($response->getType() === 'collection') {
                    $attribute_type = sprintf('array[%s]', $attribute_type);
                }
            }

            $blueprint .= sprintf('+ Attributes (%s)', $attribute_type);
            $blueprint .= $this->line();
        }

        return $blueprint;
    }

    /**
     * Recursively process an array of representation fields.
     *
     * @param array $fields
     * @param int $indent
     * @return string
     */
    private function processRepresentationFields(array $fields = [], int $indent = 2): string
    {
        $blueprint = '';

        /** @var array $field */
        foreach ($fields as $field_name => $field) {
            $blueprint .= $this->tab($indent);

            $data = [];
            if (isset($field[Documentation::DOT_NOTATION_ANNOTATION_DATA_KEY])) {
                /** @var array $data */
                $data = $field[Documentation::DOT_NOTATION_ANNOTATION_DATA_KEY];
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
                    '- `%s`%s (%s%s) - %s',
                    $field_name,
                    ($sample_data !== false) ? sprintf(': `%s`', $sample_data) : '',
                    $type,
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
            unset($field[Documentation::DOT_NOTATION_ANNOTATION_DATA_KEY]);
            if (!empty($field)) {
                // If this is an array, and has a subtype of object, we should indent a bit so we can properly render
                // out the array objects.
                if (!empty($data) && isset($data['subtype']) && $data['subtype'] === 'object') {
                    $blueprint .= $this->tab($indent + 1);
                    $blueprint .= ' - (object)';
                    $blueprint .= $this->line();

                    $blueprint .= $this->processRepresentationFields($field, $indent + 2);
                } else {
                    $blueprint .= $this->processRepresentationFields($field, $indent + 1);
                }
            }
        }

        return $blueprint;
    }

    /**
     * Given an array of resource groups, and representation structures, build a combined API Blueprint file.
     *
     * @param array $groups
     * @param array $structures
     * @return string
     */
    protected function processCombinedFile(array $groups = [], array $structures = []): string
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

            // Keep things tidy in the combined file.
            ksort($structures);

            $blueprint .= '# Data Structures';
            $blueprint .= $this->line();

            $blueprint .= implode($this->line(2), $structures);
        }

        $blueprint = trim($blueprint);

        return $blueprint;
    }

    /**
     * Convert an MSON sample data into an API Blueprint-compatible piece of data for that appropriate field type.
     *
     * @param bool|string $data
     * @param string $type
     * @return bool|string
     */
    private function convertSampleDataToCompatibleDataType($data, string $type)
    {
        if ($type == 'boolean') {
            if ($data === '0') {
                return 'false';
            } elseif ($data === '1') {
                return 'true';
            }
        }

        return $data;
    }

    /**
     * Convert a Mill-supported documentation into an API Blueprint-compatible type.
     *
     * @link https://github.com/apiaryio/mson/blob/master/MSON%20Specification.md#2-types
     * @param string $type
     * @param false|string $subtype
     * @return string
     */
    private function convertTypeToCompatibleType(string $type, $subtype = false): string
    {
        switch ($type) {
            case 'enum':
                return 'enum[string]';
                break;

            case 'float':
            case 'integer':
                return 'number';
                break;

            // API Blueprint doesn't have support for dates, timestamps, or URI's, but we still want to
            // keep that metadata in our documentation (because they might be useful if you're using Mill as an API
            // to display your documentation in some other format), so just convert these on the fly to strings so
            // they're able pass blueprint validation.
            case 'date':
            case 'datetime':
            case 'timestamp':
            case 'uri':
                return 'string';
                break;

            case 'array':
                if ($subtype) {
                    $representation = $this->getRepresentation($subtype);
                    if ($representation) {
                        return 'array[' . $representation->getLabel() . ']';
                    } elseif ($subtype !== 'object') {
                        return 'array[' . $subtype . ']';
                    }
                }

                return 'array';
                break;

            default:
                $representation = $this->getRepresentation($type);
                if ($representation) {
                    return $representation->getLabel();
                }
                break;
        }

        return $type;
    }

    /**
     * Pull a representation from the current versioned set of representations.
     *
     * @param string $representation
     * @return false|\Mill\Parser\Representation\Documentation
     */
    private function getRepresentation(string $representation)
    {
        return (isset($this->representations[$representation])) ? $this->representations[$representation] : false;
    }
}
