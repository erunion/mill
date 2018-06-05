<?php
namespace Mill\Compiler\Changelog\Changesets;

use Mill\Compiler\Changelog;
use Mill\Compiler\Changelog\Changeset;

class ActionReturn extends Changeset
{
    /**
     * {@inheritDoc}
     */
    public function getTemplates(): array
    {
        return [
            'plural' => [
                Changelog::DEFINITION_ADDED => 'The {method} on {path} now returns the following responses:',
                Changelog::DEFINITION_REMOVED => 'The {method} on {path} no longer returns the following responses:'
            ],
            'singular' => [
                Changelog::DEFINITION_ADDED => 'On {path}, {method} requests now return a {http_code} with a ' .
                    '{representation} representation.',
                Changelog::DEFINITION_REMOVED => 'On {path}, {method} requests no longer return a {http_code} with a ' .
                    '{representation} representation.',

                // Representations are optional on returns, so we need special strings for those cases.
                'no_representation' => [
                    Changelog::DEFINITION_ADDED => '{method} on {path} now returns a {http_code}.',
                    Changelog::DEFINITION_REMOVED => '{method} on {path} no longer returns a {http_code}.'
                ]
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
            if ($change['representation']) {
                $template = $templates['singular'][$definition];
            } else {
                $template = $templates['singular']['no_representation'][$definition];
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

                $template = $templates['plural'][$definition];
                $entries[] = [
                    $this->renderText($template, [
                        'resource_group' => $changes[0]['resource_group'],
                        'method' => $method,
                        'path' => $changes[0]['path']
                    ]),
                    $returns
                ];

                continue;
            }

            $change = array_shift($changes);
            $template = $templates['singular'][$definition];
            $entries[] = $this->renderText($template, $change);
        }

        return $entries;
    }

    /**
     * {@inheritDoc}
     */
    public function compileChangedChangeset(string $definition, array $changes = [])
    {
        throw new \Exception($definition . ' action return changes are not yet supported.');
    }
}
