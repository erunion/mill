<?php
namespace Mill\Generator\Changelog\Changesets;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Changeset;

class ContentType extends Changeset
{
    /**
     * @var array
     */
    protected $templates = [
        'singular' => [
            Changelog::DEFINITION_CHANGED => 'On {uri}, {method} requests will return a {content_type} Content-Type ' .
                'header.'
        ]
    ];

    /**
     * @inheritdoc
     */
    public function compileAddedOrRemovedChangeset($definition, array $changes = [])
    {
        throw new \Exception($definition . ' content type changes are not yet supported.');
    }

    /**
     * @inheritdoc
     */
    public function compileChangedChangeset($definition, array $changes = [])
    {
        if (count($changes) > 1) {
            $uris = array_map(function ($change) {
                return $change['uri'];
            }, $changes);

            // Changes are hashed and grouped by their hashes (sans URI), so it's safe to just pass along this change
            // into the template engine to build a string.
            $change = array_shift($changes);
            $change['uri'] = $uris;
        } else {
            $change = array_shift($changes);
        }

        $template = $this->templates['singular'][$definition];
        return $this->renderText($template, $change);
    }
}
