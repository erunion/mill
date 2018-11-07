<?php
namespace Mill\Compiler\Changelog\Formats;

use Mill\Compiler\Changelog;
use Mill\Compiler\Traits;

class Markdown extends Json
{
    use Traits\Markdown;

    /** @var string */
    protected $output_format = Changelog::FORMAT_MARKDOWN;

    /** @var array */
    protected $markdown = '';

    /**
     * Take compiled API documentation and convert it into a Markdown-based changelog over the life of the API.
     *
     * @throws \Exception
     */
    public function compile(): void
    {
        parent::compile();

        $api_name = $this->config->getName();
        if (!empty($api_name)) {
            $this->markdown .= sprintf('# Changelog: %s', $api_name);
            $this->markdown .= $this->line(2);
        } else {
            $this->markdown .= sprintf('# Changelog', $api_name);
            $this->markdown .= $this->line(2);
        }

        $changelog = parent::getCompiled();
        $changelog = array_shift($changelog);
        $changelog = json_decode($changelog, true);
        foreach ($changelog as $version => $data) {
            $this->markdown .= sprintf('## %s (%s)', $version, $data['_details']['release_date']);
            $this->markdown .= $this->line();

            if (isset($data['_details']['description'])) {
                $this->markdown .= sprintf('%s', $data['_details']['description']);
                $this->markdown .= $this->line(2);
            }

            $this->markdown .= '### Reference';
            $this->markdown .= $this->line();

            foreach ($data as $definition => $changes) {
                if ($definition === '_details') {
                    continue;
                }

                $this->markdown .= sprintf('#### %s', ucwords($definition));
                $this->markdown .= $this->line();

                foreach ($changes as $type => $changesets) {
                    $this->markdown .= sprintf('##### %s', ucwords($type));
                    $this->markdown .= $this->line();

                    foreach ($changesets as $changeset) {
                        $this->markdown .= $this->getChangesetMarkdown($changeset);
                    }

                    $this->markdown .= $this->line();
                }
            }
        }
    }

    /**
     * Get Markdown syntax for a given changeset.
     *
     * @param array|string $changeset
     * @param int $tab
     * @return string
     */
    private function getChangesetMarkdown($changeset, int $tab = 0): string
    {
        $markdown = '';
        if (!is_array($changeset)) {
            $markdown .= $this->tab($tab);
            $markdown .= sprintf('- %s', $changeset);
            $markdown .= $this->line();
            return $markdown;
        }

        foreach ($changeset as $change) {
            if (is_array($change)) {
                foreach ($change as $item) {
                    if (is_array($item)) {
                        $markdown .= $this->getChangesetMarkdown($item, $tab + 1);
                        continue;
                    }

                    $markdown .= $this->tab($tab + 1);
                    $markdown .= sprintf('- %s', $item);
                    $markdown .= $this->line();
                }

                continue;
            }

            $markdown .= $this->tab($tab);
            $markdown .= sprintf('- %s', $change);
            $markdown .= $this->line();
        }

        return $markdown;
    }

    /**
     * @return array
     */
    public function getCompiled(): array
    {
        if (empty($this->markdown)) {
            $this->compile();
        }

        return [$this->markdown];
    }
}
