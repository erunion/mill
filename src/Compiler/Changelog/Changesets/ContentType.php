<?php
namespace Mill\Compiler\Changelog\Changesets;

use Mill\Compiler\Changelog;
use Mill\Compiler\Changelog\Changeset;

class ContentType extends Changeset
{
    /**
     * {@inheritDoc}
     */
    public function getTemplates(): array
    {
        return [
            'singular' => [
                Changelog::DEFINITION_CHANGED => 'On {path}, {method} requests now return a {content_type} ' .
                    'Content-Type header.'
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function compileAddedOrRemovedChangeset(string $definition, array $changes = [])
    {
        throw new \Exception($definition . ' content type changes are not yet supported.');
    }

    /**
     * {@inheritDoc}
     */
    public function compileChangedChangeset(string $definition, array $changes = [])
    {
        $templates = $this->getTemplates();

        if (count($changes) > 1) {
            $paths = array_map(function (array $change): string {
                return $change['path'];
            }, $changes);

            // Changes are hashed and grouped by their hashes (sans path), so it's safe to just pass along this change
            // into the template engine to build a string.
            $change = array_shift($changes);
            $change['path'] = $paths;
        } else {
            $change = array_shift($changes);
        }

        $template = $templates['singular'][$definition];
        return $this->renderText($template, $change);
    }
}
