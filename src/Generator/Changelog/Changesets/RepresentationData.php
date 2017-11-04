<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Changeset;

class RepresentationData extends Changeset
{
    /**
     * {@inheritDoc}
     */
    public function getTemplates(): array
    {
        return [
            'plural' => [
                Changelog::DEFINITION_ADDED => 'The {representation} representation has added the following fields:',
                Changelog::DEFINITION_REMOVED => 'The {representation} representation has removed the following fields:'
            ],
            'singular' => [
                Changelog::DEFINITION_ADDED => '{field} has been added to the {representation} representation.',
                Changelog::DEFINITION_REMOVED => '{field} has been removed from the {representation} representation.'
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

        $fields = [];
        foreach ($changes as $change) {
            $fields[] = $this->renderText('{field}', $change);
        }

        $template = $templates['plural'][$definition];
        return [
            $this->renderText($template, array_shift($changes)),
            $fields
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function compileChangedChangeset(string $definition, array $changes = [])
    {
        throw new \Exception($definition . ' representation data changes are not yet supported.');
    }
}
