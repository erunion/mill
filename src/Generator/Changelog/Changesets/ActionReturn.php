<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog\Changeset;

class ActionReturn extends Changeset
{
    /**
     * @var array
     */
    protected $templates = [
        'plural' => [
            'added' => 'The {method} on {uri} will now return the following responses:',
            'removed' => 'The {method} on {uri} no longer returns the following responses:'
        ],
        'singular' => [
            'added' => 'On {uri}, {method} requests now returns a {http_code} with a {representation} ' .
                'representation.',
            'removed' => 'On {uri}, {method} requests no longer returns a {http_code} with a {representation} ' .
                'representation.',

            // Representations are optional on returns, so we need special strings for those cases.
            'added_no_representation' => '{method} on {uri} now returns a {http_code}.',
            'removed_no_representation' => '{method} on {uri} no longer will return a {http_code}.'
        ]
    ];

    /**
     * @inheritdoc
     */
    public function compileAddedOrRemovedChangeset($definition, array $changes = [])
    {
        if (count($changes) === 1) {
            $change = array_shift($changes);
            if ($change['representation']) {
                $template = $this->templates['singular'][$definition];
            } else {
                $definition .= '_no_representation';
                $template = $this->templates['singular'][$definition];
            }

            return $this->renderText($template, $change);
        }

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

                $template = $this->templates['plural'][$definition];
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
            $template = $this->templates['singular'][$definition];
            $entries[] = $this->renderText($template, $change);
        }

        return $entries;
    }

    /**
     * @inheritdoc
     */
    public function compileChangedChangeset($definition, array $changes = [])
    {
        throw new \Exception($definition . ' action return changes are not yet supported.');
    }
}
