<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog\Changeset;

class ActionParam extends Changeset
{
    /**
     * @var array
     */
    protected $templates = [
        'plural' => [
            'added' => 'The following parameters have been added to {method} on {uri}:',
            'removed' => 'The following parameters have been removed to {method} on {uri}:'
        ],
        'singular' => [
            'added' => 'A {parameter} request parameter was added to {method} on {uri}.',
            'removed' => 'The {parameter} request parameter has been removed from {method} requests on {uri}.'
        ]
    ];

    /**
     * @inheritdoc
     */
    public function compileAddedOrRemovedChangeset($definition, array $changes = [])
    {
        if (count($changes) === 1) {
            $change = array_shift($changes);
            $template = $this->templates['singular'][$definition];
            return $this->renderText($template, $change);
        }

        $methods = [];
        foreach ($changes as $change) {
            $methods[$change['method']][] = $this->renderText('{parameter}', $change);
        }

        $entry = [];
        foreach ($methods as $method => $params) {
            if (count($params) > 1) {
                $template = $this->templates['plural'][$definition];
                $entry[] = [
                    $this->renderText($template, [
                        'method' => $method,
                        'uri' => $changes[0]['uri']
                    ]),
                    $params
                ];

                continue;
            }

            $template = $this->templates['singular'][$definition];
            $entry[] = $this->renderText($template, [
                'parameter' => rtrim(ltrim(array_shift($params), '`'), '`'),
                'method' => $method,
                'uri' => $changes[0]['uri']
            ]);
        }

        return $entry;
    }

    /**
     * @inheritdoc
     */
    public function compileChangedChangeset($definition, array $changes = [])
    {
        throw new \Exception($definition . ' action param changes are not yet supported.');
    }
}
