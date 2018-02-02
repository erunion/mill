<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Changeset;

class ActionParam extends Changeset
{
    /**
     * {@inheritDoc}
     */
    public function getTemplates(): array
    {
        return [
            'plural' => [
                Changelog::DEFINITION_ADDED => 'The following parameters have been added to {method} on {uri}:',
                Changelog::DEFINITION_REMOVED => 'The following parameters have been removed from {method} on {uri}:'
            ],
            'singular' => [
                Changelog::DEFINITION_ADDED => 'A {parameter} request parameter was added to {method} on {uri}.',
                Changelog::DEFINITION_REMOVED => 'The {parameter} request parameter has been removed from {method} ' .
                    'requests on {uri}.'
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function compileAddedOrRemovedChangeset(string $definition, array $changes = [])
    {
        $templates = $this->getTemplates();

        if (count($changes) === 1) {
            $change = array_shift($changes);
            $template = $templates['singular'][$definition];
            return $this->renderText($template, $change);
        }

        $methods = [];
        foreach ($changes as $change) {
            $methods[$change['method']][] = $change['parameter'];
        }

        $entry = [];
        foreach ($methods as $method => $params) {
            if (count($params) > 1) {
                // Templatize the parameters before passing them into the entries array. Would prefer to do this as an
                // array_map call, but you can't pass `$this` into closures.
                foreach ($params as $k => $param) {
                    $params[$k] = $this->renderText('{parameter}', [
                        'parameter' => $param
                    ]);
                }

                $template = $templates['plural'][$definition];
                $entry[] = [
                    $this->renderText($template, [
                        'resource_namespace' => $changes[0]['resource_namespace'],
                        'method' => $method,
                        'uri' => $changes[0]['uri']
                    ]),
                    $params
                ];

                continue;
            }

            $template = $templates['singular'][$definition];
            $entry[] = $this->renderText($template, [
                'resource_namespace' => $changes[0]['resource_namespace'],
                'parameter' => array_shift($params),
                'method' => $method,
                'uri' => $changes[0]['uri']
            ]);
        }

        return $entry;
    }

    /**
     * {@inheritDoc}
     */
    public function compileChangedChangeset(string $definition, array $changes = [])
    {
        throw new \Exception($definition . ' action param changes are not yet supported.');
    }
}
