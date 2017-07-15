<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog\Changeset;

class Action extends Changeset
{
    /**
     * @var array
     */
    protected $templates = [
        'plural' => [
            'added' => '{uri} has been added with support for the following HTTP methods:'
        ],
        'singular' => [
            'added' => '{method} on {uri} was added.'
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
            $methods[] = $this->renderText('{method}', $change);
        }

        $template = $this->templates['plural'][$definition];
        return [
            [
                // Changes are grouped by URIs so it's safe to just pull the first URI here.
                $this->renderText($template, [
                    'uri' => $changes[0]['uri']
                ]),
                $methods
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function compileChangedChangeset($definition, array $changes = [])
    {
        throw new \Exception($definition . ' action changes are not yet supported.');
    }
}
