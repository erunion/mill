<?php
namespace Mill\Compiler\Changelog\Changesets;

use Mill\Compiler\Changelog;
use Mill\Compiler\Changelog\Changeset;

class Action extends Changeset
{
    /**
     * {@inheritDoc}
     */
    public function getTemplates(): array
    {
        return [
            'plural' => [
                Changelog::DEFINITION_ADDED => '{path} has been added with support for the following HTTP methods:'
            ],
            'singular' => [
                Changelog::DEFINITION_ADDED => '{method} on {path} was added.'
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
            $methods[] = $this->renderText('{method}', $change);
        }

        $template = $templates['plural'][$definition];
        return [
            [
                // Changes are grouped by paths so it's safe to just pull the first path here.
                $this->renderText($template, [
                    'resource_group' => $changes[0]['resource_group'],
                    'path' => $changes[0]['path']
                ]),
                $methods
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function compileChangedChangeset(string $definition, array $changes = [])
    {
        throw new \Exception($definition . ' action changes are not yet supported.');
    }
}
