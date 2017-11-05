<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Changeset;

class Action extends Changeset
{
    /**
     * {@inheritDoc}
     */
    public function getTemplates(): array
    {
        return [
            'plural' => [
                Changelog::DEFINITION_ADDED => '{uri} has been added with support for the following HTTP methods:'
            ],
            'singular' => [
                Changelog::DEFINITION_ADDED => '{method} on {uri} was added.'
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
                // Changes are grouped by URIs so it's safe to just pull the first URI here.
                $this->renderText($template, [
                    'resource_namespace' => $changes[0]['resource_namespace'],
                    'uri' => $changes[0]['uri']
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
