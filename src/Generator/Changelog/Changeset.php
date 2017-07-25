<?php
namespace Mill\Generator\Changelog;

use Mill\Generator\Traits\ChangelogTemplate;

abstract class Changeset
{
    use ChangelogTemplate;

    /**
     * Get the templates that this changeset will use to generate changesets.
     *
     * @return array
     */
    abstract public function getTemplates();

    /**
     * Get a changelog entry for a changeset that was added into, or removed from, the API.
     *
     * @param string $definition This is the definition of the changeset, whether it's an "added", "removed", or
     *  "changed" set.
     * @param array $changes
     * @return string|array
     * @throws \Exception If an unsupported definition + change type was supplied.
     */
    abstract public function compileAddedOrRemovedChangeset($definition, array $changes = []);

    /**
     * Get a changelog entry for a changeset that was changed within the API.
     *
     * @param string $definition This is the definition of the changeset, whether it's an "added", "removed", or
     *  "changed" set.
     * @param array $changes
     * @return string|array
     * @throws \Exception If an unsupported definition + change type was supplied.
     */
    abstract public function compileChangedChangeset($definition, array $changes = []);
}
