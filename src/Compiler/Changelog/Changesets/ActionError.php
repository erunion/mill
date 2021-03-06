<?php
namespace Mill\Compiler\Changelog\Changesets;

use Mill\Compiler\Changelog;
use Mill\Compiler\Changelog\Changeset;

class ActionError extends Changeset
{
    /**
     * {@inheritDoc}
     */
    public function getTemplates(): array
    {
        return [
            'plural' => [
                Changelog::DEFINITION_ADDED => '{path} now returns the following errors on {method} requests:',
                Changelog::DEFINITION_REMOVED => '{path} no longer returns the following errors on {method} requests:'
            ],
            'singular' => [
                Changelog::DEFINITION_ADDED => 'On {method} requests to {path}, a {http_code} with a ' .
                    '{representation} representation is now returned: {description}',
                Changelog::DEFINITION_REMOVED => '{method} requests to {path} no longer returns a {http_code} with a ' .
                    '{representation} representation: {description}'
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
            $methods[$change['method']][] = $change;
        }

        $entries = [];
        foreach ($methods as $method => $changes) {
            if (count($changes) > 1) {
                $errors = [];
                foreach ($changes as $change) {
                    $errors[] = $this->renderText(
                        '{http_code} with a {representation} representation: {description}',
                        $change
                    );
                }

                $change = array_shift($changes);

                $template = $templates['plural'][$definition];
                $entries[] = [
                    $this->renderText($template, [
                        'resource_group' => $change['resource_group'],
                        'method' => $method,
                        'path' => $change['path']
                    ]),
                    array_unique($errors)
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
        throw new \Exception($definition . ' action error changes are not yet supported.');
    }
}
