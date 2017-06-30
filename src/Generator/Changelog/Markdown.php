<?php
namespace Mill\Generator\Changelog;

use Mill\Generator\Traits;

class Markdown extends Json
{
    use Traits\Markdown;

    /**
     * Take compiled API documentation and generate a Markdown-based changelog over the life of the API.
     *
     * @return string
     */
    public function generate()
    {
        $markdown = '';

        $api_name = $this->config->getName();
        if (!empty($api_name)) {
            $markdown .= sprintf('# Changelog: %s', $api_name);
            $markdown .= $this->line(2);
        } else {
            $markdown .= sprintf('# Changelog', $api_name);
            $markdown .= $this->line(2);
        }

        $changelog = json_decode(parent::generate(), true);
        foreach ($changelog as $version => $data) {
            $markdown .= sprintf('## %s', $version);
            $markdown .= $this->line();

            foreach ($data as $type => $changes) {
                $markdown .= sprintf('### %s', ucwords($type));
                $markdown .= $this->line();

                foreach ($changes as $section => $changesets) {
                    $markdown .= sprintf('#### %s', ucwords($section));
                    $markdown .= $this->line();

                    foreach ($changesets as $changeset) {
                        $markdown .= $this->getChangesetMarkdown($changeset);
                    }

                    $markdown .= $this->line();
                }
            }
        }

        return $markdown;
    }

    /**
     * Get Markdown syntax for a given changeset.
     *
     * @param array|string $changeset
     * @return string
     */
    private function getChangesetMarkdown($changeset)
    {
        $markdown = '';

        if (!is_array($changeset)) {
            $markdown .= sprintf('- %s', $changeset);
            $markdown .= $this->line();
            return $markdown;
        }

        foreach ($changeset as $change) {
            if (!is_array($change)) {
                $markdown .= sprintf('- %s', $change);
                $markdown .= $this->line();
                continue;
            }

            $markdown .= sprintf('- %s', array_shift($change));
            $markdown .= $this->line();
            foreach (array_shift($change) as $item) {
                $markdown .= $this->tab();
                $markdown .= sprintf('- %s', $item);
                $markdown .= $this->line();
            }
        }

        return $markdown;
    }

    /**
     * Render a changelog template with some content.
     *
     * @param string $template
     * @param array $content
     * @return string
     */
    public function renderText($template, array $content)
    {
        $searches = [];
        $replacements = [];
        foreach ($content as $key => $value) {
            switch ($key) {
                case 'content_type':
                case 'field':
                case 'http_code':
                case 'method':
                case 'parameter':
                case 'representation':
                case 'uri':
                    $searches[] = '{' . $key . '}';
                    $replacements[] = '`{' . $key . '}`';
                    break;

                case 'description':
                default:
                    // do nothing
            }
        }

        $template = str_replace($searches, $replacements, $template);

        return $this->template_engine->render($template, $content);
    }
}
