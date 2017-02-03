<?php
namespace Mill\Generator;

use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Generator;
use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Annotations\ScopeAnnotation;
use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Annotations\UriSegmentAnnotation;
use Mill\Parser\Resource\Action;

class Blueprint extends Generator
{
    /**
     * Take compiled API documentation and generate API Blueprint representations.
     *
     * @return array
     */
    public function generate()
    {
        parent::generate();

        $resources = $this->getResources();

        $blueprints = [];

        /** @var array $data */
        foreach ($resources as $version => $groups) {
            $this->version = $version;

            foreach ($groups as $group_name => $data) {
                if (in_array($group_name, ['/', 'OAuth'])) {
                    continue;
                }

                $contents = 'FORMAT: 1A';
                $contents .= $this->line(2);

                $contents .= sprintf('# %s', $group_name);
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
                $blueprints[$this->version][$group_name] = $contents;
            }
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

            $blueprint .= $this->tab();
            $blueprint .= sprintf(
                '+ `%s` (%s, required) - %s',
                $field,
                $segment->getType(),
                $segment->getDescription()
            );

            $blueprint .= $this->line();
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

        $blueprint .= $this->tab();
        $blueprint .= '+ Attributes';
        $blueprint .= $this->line();

        $has_visible_params = false;

        /** @var ParamAnnotation $param */
        foreach ($params as $param) {
            $has_visible_params = true;

            $values = $param->getValues();

            $blueprint .= $this->tab(2);
            $blueprint .= sprintf(
                '- `%s` (%s%s) - %s',
                $param->getField(),
                (!empty($values)) ? 'enum[' . $param->getType() . ']' : 'string',
                ($param->isRequired()) ? ', required' : null,
                $param->getDescription()
            );

            $blueprint .= $this->line();

            if (!empty($values)) {
                $blueprint .= $this->tab(3);
                $blueprint .= '+ Members';
                $blueprint .= $this->line();

                foreach ($values as $value) {
                    $blueprint .= $this->tab(4);
                    $blueprint .= '+ `' . $value . '`';
                    $blueprint .= $this->line();
                }
            }
        }

        if (!$has_visible_params) {
            return '';
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

        $blueprint = '+ Response ' . $http_code . ' (' . $action->getContentType() . ')';
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

                    // Only really need descriptions for non-200 responses.
                    if ($http_code >= 300 && empty($description)) {
                        throw new \Exception(
                            sprintf(
                                'The non-200 response, %s, in %s %s is missing a description.',
                                $http_code,
                                $action->getMethod(),
                                $action->getUri()->getPath()
                            )
                        );
                    }

                    $description = (!empty($description)) ? $description : 'Standard request.';

                    $blueprint .= $this->tab(2);
                    $blueprint .= ' * ' . $description;
                    $blueprint .= $this->line();
                }
            }
        }

        /** @var ReturnAnnotation|ThrowsAnnotation $response */
        $response = array_shift($responses);
        $representation = $response->getRepresentation();
        $representations = $this->getRepresentations($this->version);

        // There's rare, and highly discouraged, instances where you might be using a representation that is being
        // ignored, and is devoid of any documentation; like `@api-return:public {200} string`
        if (!isset($representations[$representation])) {
            return $blueprint;
        }

        /** @var \Mill\Parser\Representation\Documentation $docs */
        $docs = $representations[$representation];
        $fields = $docs->getExplodedContentDotNotation();
        if (!empty($fields)) {
            $blueprint .= $this->tab();
            $blueprint .= '+ Attributes';
            $blueprint .= $this->line();

            $blueprint .= $this->processRepresentationFields($fields);
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
            if (isset($field['__FIELD_DATA__'])) {
                /** @var array $data */
                $data = $field['__FIELD_DATA__'];
                switch ($data['type']) {
                    case 'enum':
                        $type = 'enum[string]';
                        break;

                    // @todo set this to the name of the response and embed the full docs for that response type
                    case 'representation':
                        // https://apiblueprint.org/documentation/mson/specification.html#22-named-types
                        $type = 'object'; //'<<@todo REPRESENTATION>>';
                        break;

                    // API Blueprint doesn't have support for dates, timestamps, or URI's, but we still want to
                    // keep that metadata in our comments, so just convert these on the fly to strings so they
                    // pass blueprint validation.
                    case 'datetime':
                    case 'timestamp':
                    case 'uri':
                        $type = 'string';
                        break;

                    default:
                        $type = $data['type'];
                }

                //$blueprint .= $this->tab($indent);
                $blueprint .= sprintf('- `%s` (%s) - %s', $field_name, $type, $data['label']);
                $blueprint .= $this->line();

                // Only enum's support options/members.
                if ($data['type'] === 'enum' && !empty($data['options'])) {
                    $blueprint .= $this->tab($indent + 1);
                    $blueprint .= '+ Members';
                    $blueprint .= $this->line();

                    foreach ($data['options'] as $value) {
                        $blueprint .= $this->tab($indent + 2);
                        $blueprint .= '+ `' . $value . '`';
                        $blueprint .= $this->line();
                    }
                }
            } else {
                $blueprint .= sprintf('- `%s` (object)', $field_name);
                $blueprint .= $this->line();
            }

            // Process any exploded dot notation children of this field.
            if (count($field) > 1) {
                unset($field['__FIELD_DATA__']);

                // If this is an array, and has a subtype of object, we should intent a bit so we can properly render
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
}
