<?php
namespace Mill\Generator\Changelog;

use Mill\Generator\Traits\ChangelogTemplate;

abstract class Changeset
{
    use ChangelogTemplate;

    /**
     * Get a changelog entry for a changeset that was added into, or removed from, the API.
     *
     * @param string $definition
     * @param array $changes
     * @return string|array
     * @throws \Exception If an unsupported definition + change type was supplied.
     */
    abstract protected function compileAddedOrRemovedChangeset($definition, array $changes = []);

    /**
     * Get a changelog entry for a changeset that was changed within the API.
     *
     * @param string $definition
     * @param array $changes
     * @return string|array
     * @throws \Exception If an unsupported definition + change type was supplied.
     */
    abstract protected function compileChangedChangeset($definition, array $changes = []);
}
