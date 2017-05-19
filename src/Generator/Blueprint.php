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
    /**
     * Current list of representations for the current API version we're working with.
     * @var array
     */
    private $representations = [];

    /**
     * Take compiled API documentation and generate API Blueprint representations.
     *
     * @return array
     */
    public function generate()
    {
        parent::generate();

        $group_excludes = $this->config->getBlueprintGroupExcludes();
        $resources = $this->getResources();

        $blueprints = [];

        /** @var array $data */
        foreach ($resources as $version => $groups) {
            $this->version = $version;
            $this->representations = $this->getRepresentations($this->version);

            // Process resource groups.
            foreach ($groups as $group_name => $data) {
                // If this group has been designated in the config file to be excluded, then exclude it.
                if (in_array($group_name, $group_excludes)) {
                    continue;
                }

                $contents = sprintf('# Group %s', $group_name);
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

                        // Generate scopes
                        $action_contents = $this->processScopes($action);

                        // Process parameters
                        $action_contents .= $this->processParameters($action);

                        // Generate request
                        $action_contents .= $this->processRequest($action);

                        // Generate response
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
                $blueprints[$this->version]['groups'][$group_name] = $contents;
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
     * @return string|false
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
            'This action requires a bearer token with %s scope%s.',
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
     * @return string|false
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
    protected function processRequest(Action\Documentation $action)
    {
        $params = $action->getParameters();
        if (empty($params)) {
            return '';
        }

        $blueprint = '+ Request';
        $blueprint .= $this->line();

        // Build up request headers.
        $blueprint .= $this->tab();
        $blueprint .= '+ Headers';
        $blueprint .= $this->line(2);

        $blueprint .= $this->tab(3);
        $blueprint .= sprintf('Content-Type: %s', $action->getContentType($this->version));
        $blueprint .= $this->line(2);

        // Build up request attributes.
        $blueprint .= $this->tab();
        $blueprint .= '+ Attributes';
        $blueprint .= $this->line();

        /** @var ParamAnnotation $param */
        foreach ($params as $param) {
            $sample_data = $param->getSampleData();
            $values = $param->getValues();
            $type = $this->convertTypeToCompatibleType($param->getType());

            $blueprint .= $this->tab(2);
            $blueprint .= sprintf(
                '- `%s`%s (%s%s) - %s',
                $param->getField(),
                (!empty($sample_data)) ? sprintf(': `%s`', $sample_data) : '',
                (!empty($values)) ? 'enum[' . $type . ']' : $type,
                ($param->isRequired()) ? ', required' : null,
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
    protected function processResponses(Action\Documentation $action, $http_code, array $responses = [])
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
                $blueprint .= $this->tab();
                $blueprint .= sprintf('There are %d ways that this status code can be encountered.', count($responses));
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
     * @param integer $indent
     * @return string
     */
    private function processRepresentationFields($fields = [], $indent = 2)
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

                $blueprint .= sprintf(
                    '- `%s`%s (%s) - %s',
                    $field_name,
                    (!empty($data['sample_data'])) ? sprintf(': `%s`', $data['sample_data']) : '',
                    $type,
                    $data['description']
                );

                $blueprint .= $this->line();

                // Only enum's support options/members.
                if ($data['type'] === 'enum' && !empty($data['values'])) {
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
    protected function processCombinedFile($groups = [], $structures = [])
    {
        $blueprint = 'FORMAT: 1A';
        $blueprint .= $this->line(2);

        $api_name = $this->config->getName();
        if (!empty($api_name)) {
            $blueprint .= sprintf('# %s', $api_name);
            $blueprint .= $this->line();

            $blueprint .= sprintf("This is the API Blueprint file for %s.", $api_name);
            $blueprint .= $this->line(2);

            $blueprint .= sprintf(
                "It was automatically generated by [Mill](https://github.com/vimeo/mill) on %s.",
                (new \DateTime())->format('Y-m-d H:i:s')
            );

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
     * Return a repeated new line character.
     *
     * @param integer $repeat
     * @return string
     */
    protected function line($repeat = 1)
    {
        return str_repeat("\n", $repeat);
    }

    /**
     * Return a repeated tab character.
     *
     * @param integer $repeat
     * @return string
     */
    protected function tab($repeat = 1)
    {
        return str_repeat('    ', $repeat);
    }

    /**
     * Convert a Mill-supported documentation into an API Blueprint-compatible type.
     *
     * @link https://github.com/apiaryio/mson/blob/master/MSON%20Specification.md#2-types
     * @param string $type
     * @param mixed $subtype
     * @return string
     */
    private function convertTypeToCompatibleType($type, $subtype = false)
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
     * @return \Mill\Parser\Representation\Documentation|false
     */
    private function getRepresentation($representation)
    {
        return (isset($this->representations[$representation])) ? $this->representations[$representation] : false;
    }
}
