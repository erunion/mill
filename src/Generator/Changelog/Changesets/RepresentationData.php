<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Changeset;

class RepresentationData extends Changeset
{
    /**
     * @var array
     */
    protected $templates = [
        'plural' => [
            Changelog::DEFINITION_ADDED => 'The {representation} representation has added the following fields:',
            Changelog::DEFINITION_REMOVED => 'The {representation} representation has removed the following fields:'
        ],
        'singular' => [
            Changelog::DEFINITION_ADDED => '{field} has been added to the {representation} representation.',
            Changelog::DEFINITION_REMOVED => '{field} has been removed from the {representation} representation.'
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

        $fields = [];
        foreach ($changes as $change) {
            $fields[] = $this->renderText('{field}', $change);
        }

        $template = $this->templates['plural'][$definition];
        return [
            $this->renderText($template, array_shift($changes)),
            $fields
        ];
    }

    /**
     * @inheritdoc
     */
    public function compileChangedChangeset($definition, array $changes = [])
    {
        throw new \Exception($definition . ' representation data changes are not yet supported.');
    }
}
