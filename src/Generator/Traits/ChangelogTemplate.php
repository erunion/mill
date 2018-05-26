<?php
namespace Mill\Generator\Traits;

use Mill\Generator\Changelog;
use StringTemplate\Engine;

trait ChangelogTemplate
{
    /**
     * Changelog template output format.
     *
     * @var string
     */
    protected $output_format = Changelog::FORMAT_JSON;

    /**
     * @var Engine|null
     */
    protected $template_engine;

    /**
     * Render a template with some content.
     *
     * @param string $template
     * @param array $content
     * @return string
     */
    protected function renderText(string $template, array $content = []): string
    {
        if (is_null($this->template_engine)) {
            $this->template_engine = new Engine;
        }

        if ($this->output_format === Changelog::FORMAT_JSON) {
            list($template, $content) = $this->transformTemplateIntoHtml($template, $content);
        } else {
            list($template, $content) = $this->transformTemplateIntoMarkdown($template, $content);
        }

        return $this->template_engine->render($template, $content);
    }

    /**
     * Transform a template by wrapping specific content in styleable HTML elements.
     *
     * @param string $template
     * @param array $content
     * @return array
     */
    protected function transformTemplateIntoHtml(string $template, array $content = []): array
    {
        $data_attributes = [];
        foreach ($content as $key => $value) {
            if ($key === 'description') {
                continue;
            }

            $data_attributes[] = sprintf(
                'data-mill-%s="%s"',
                str_replace('_', '-', $key),
                $value
            );
        }

        $html = '<span class="{css_namespace}_%s" %s>%s</span>';
        $data_attributes = implode(' ', $data_attributes);

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
                case 'resource_group':
                case 'path':
                    $searches[] = '{' . $key . '}';
                    if (is_array($value)) {
                        $replacements[] = $this->joinWords(
                            array_map(function (string $value) use ($html, $key, $data_attributes): string {
                                return sprintf($html, $key, $data_attributes, $value);
                            }, $value)
                        );
                    } else {
                        $replacements[] = sprintf($html, $key, $data_attributes, $value);
                    }
                    break;

                case 'description':
                default:
                    // do nothing
            }
        }

        $template = str_replace($searches, $replacements, $template);

        $content['css_namespace'] = 'mill-changelog';

        return [$template, $content];
    }

    /**
     * Transform a template into Markdown by wrapping specific content in code-like backticks.
     *
     * @param string $template
     * @param array $content
     * @return array
     */
    protected function transformTemplateIntoMarkdown(string $template, array $content = []): array
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
                case 'path':
                    $searches[] = '{' . $key . '}';
                    if (is_array($value)) {
                        $replacements[] = $this->joinWords(
                            array_map(function (string $val): string {
                                return sprintf('`%s`', $val);
                            }, $value)
                        );
                    } else {
                        $replacements[] = sprintf('`{%s}`', $key);
                    }
                    break;

                case 'description':
                default:
                    // do nothing
            }
        }

        $template = str_replace($searches, $replacements, $template);

        return [$template, $content];
    }

    /**
     * Join an array of words into a structure for use in a sentence.
     *
     *  - [word1, word2] -> "word1 and word 2"
     *  - [word1, word2, word3] -> "word1, word2 and word 3"
     *
     * @param array $words
     * @return string
     */
    protected function joinWords(array $words): string
    {
        if (count($words) <= 2) {
            return implode(' and ', $words);
        }

        $last = array_pop($words);
        return implode(', ', $words) . ' and ' . $last;
    }

    /**
     * Set the current changelog template output format.
     *
     * @param string $format
     */
    public function setOutputFormat($format = 'json'): void
    {
        $this->output_format = $format;
    }
}
