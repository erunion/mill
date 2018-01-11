<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Changeset;

class ActionThrows extends Changeset
{
    /**
     * @inheritdoc
     */
    public function getTemplates()
    {
        return [
            'plural' => [
                Changelog::DEFINITION_ADDED => '{uri} now throws the following errors on {method} requests:',
                Changelog::DEFINITION_REMOVED => '{uri} no longer throws the following errors on {method} requests:'
            ],
            'singular' => [
                Changelog::DEFINITION_ADDED => 'On {method} requests to {uri}, a {http_code} with a {representation} ' .
                    'representation is now returned: {description}',
                Changelog::DEFINITION_REMOVED => '{method} requests to {uri} no longer returns a {http_code} with a ' .
                    '{representation} representation: {description}'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function compileAddedOrRemovedChangeset($definition, array $changes = [])
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
                        'uri' => $change['uri']
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
     * @inheritdoc
     */
    public function compileChangedChangeset($definition, array $changes = [])
    {
        throw new \Exception($definition . ' action throws changes are not yet supported.');
    }
}
