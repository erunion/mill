<?php
namespace Mill\Compiler\Changelog\Formats;

use Mill\Compiler;
use Mill\Compiler\Changelog;
use Mill\Compiler\Traits\ChangelogTemplate;

class Json extends Compiler
{
    use ChangelogTemplate;

    /** @var array Compiled changelog. */
    protected $changelog = [];

    /**
     * Set the current changelog we're going to build a representation for.
     *
     * @param array $changelog
     * @return Json
     */
    public function setChangelog(array $changelog = []): self
    {
        $this->changelog = $changelog;
        return $this;
    }

    /**
     * Take compiled API documentation and convert it into a JSON-encoded changelog over the life of the API.
     *
     * @return array
     * @throws \Exception
     */
    public function compile(): array
    {
        $json = [];

        foreach ($this->changelog as $version => $version_changes) {
            foreach ($version_changes as $definition => $data) {
                if ($definition === '_details') {
                    $json[$version][$definition] = $data;
                    continue;
                }

                foreach ($data as $type => $changesets) {
                    if ($type === 'resources') {
                        $entries = $this->parseResourceChangesets($definition, $changesets);
                    } else {
                        $entries = $this->parseRepresentationChangesets($definition, $changesets);
                    }

                    $json[$version][$definition][$type] = $entries;
                }
            }
        }

        return [
            json_encode($json)
        ];
    }

    /**
     * Parse representation changesets.
     *
     * @param string $definition
     * @param array $changesets
     * @return array
     * @throws \Exception
     */
    private function parseRepresentationChangesets(string $definition, array $changesets = []): array
    {
        $entries = [];
        foreach ($changesets as $representation => $change_types) {
            foreach ($change_types as $change_type => $hashes) {
                foreach ($hashes as $hash => $changes) {
                    if (in_array($definition, [
                        Changelog::DEFINITION_ADDED,
                        Changelog::DEFINITION_REMOVED
                    ])) {
                        $entry = $this->getAddedOrRemovedChangesetFactory($definition, $change_type, $changes);
                    } else {
                        $entry = $this->getChangedChangesetFactory($definition, $change_type, $changes);
                    }

                    // Reduce some unnecessary nesting of changeset strings.
                    if (is_array($entry) && count($entry) === 1) {
                        $entry = array_shift($entry);
                    }

                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    /**
     * Parse resource changesets.
     *
     * @param string $definition
     * @param array $changesets
     * @return array
     * @throws \Exception
     */
    private function parseResourceChangesets(string $definition, array $changesets = []): array
    {
        $entries = [];
        foreach ($changesets as $group => $data) {
            $group_entry = [
                $this->renderText('The following {resource_group} resources have ' . $definition . ':', [
                    'resource_group' => $group
                ]),
                [] // Group-related entries will be nested here.
            ];

            foreach ($data as $path => $change_types) {
                foreach ($change_types as $change_type => $hashes) {
                    foreach ($hashes as $hash => $changes) {
                        if (in_array($definition, [
                            Changelog::DEFINITION_ADDED,
                            Changelog::DEFINITION_REMOVED
                        ])) {
                            $entry = $this->getAddedOrRemovedChangesetFactory($definition, $change_type, $changes);
                        } else {
                            $entry = $this->getChangedChangesetFactory($definition, $change_type, $changes);
                        }

                        // Reduce some unnecessary nesting of changeset strings.
                        if (is_array($entry) && count($entry) === 1) {
                            $entry = array_shift($entry);
                        }

                        $group_entry[1][] = $entry;
                    }
                }
            }

            $entries[] = $group_entry;
        }

        return $entries;
    }

    /**
     * Get a changelog entry for a changeset that was added into the API.
     *
     * @param string $definition
     * @param string $change_type
     * @param array $changes
     * @return string|array
     * @throws \Exception If an unsupported definition + change type was supplied.
     */
    private function getAddedOrRemovedChangesetFactory(string $definition, string $change_type, array $changes)
    {
        switch ($change_type) {
            case Changelog::CHANGESET_TYPE_ACTION:
                $changeset = new Changelog\Changesets\Action;
                break;

            case Changelog::CHANGESET_TYPE_ACTION_PARAM:
                $changeset = new Changelog\Changesets\ActionParam;
                break;

            case Changelog::CHANGESET_TYPE_ACTION_RETURN:
                $changeset = new Changelog\Changesets\ActionReturn;
                break;

            case Changelog::CHANGESET_TYPE_ACTION_ERROR:
                $changeset = new Changelog\Changesets\ActionError;
                break;

            case Changelog::CHANGESET_TYPE_REPRESENTATION_DATA:
                $changeset = new Changelog\Changesets\RepresentationData;
                break;

            default:
                throw new \Exception($definition . ' `' . $change_type . '` changes are not yet supported.');
        }

        $changeset->setOutputFormat($this->output_format);
        return $changeset->compileAddedOrRemovedChangeset($definition, $changes);
    }

    /**
     * Get a changelog entry for a changeset that was changed in the API.
     *
     * @param string $definition
     * @param string $change_type
     * @param array $changes
     * @return string|array
     * @throws \Exception If an unsupported definition + change type was supplied.
     */
    private function getChangedChangesetFactory(string $definition, string $change_type, array $changes)
    {
        // Due to versioning restrictions in the Mill syntax (that will be fixed), only `@api-contenttype` annotations
        // will create a "changed" entry in the changelog.
        switch ($change_type) {
            case Changelog::CHANGESET_TYPE_CONTENT_TYPE:
                $changeset = new Changelog\Changesets\ContentType;
                break;

            default:
                throw new \Exception($definition . ' `' . $change_type . '` changes are not yet supported.');
        }

        $changeset->setOutputFormat($this->output_format);
        return $changeset->compileChangedChangeset($definition, $changes);
    }
}
