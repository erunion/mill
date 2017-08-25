<?php
namespace Mill\Generator\ErrorMap\Formats;

use Mill\Generator;
use Mill\Generator\ErrorMap;
use Mill\Generator\Traits;

class Markdown extends Generator
{
    use Traits\Markdown;

    /**
     * @var string
     */
    protected $output_format = ErrorMap::FORMAT_MARKDOWN;

    /**
     * Generated error map.
     *
     * @var array
     */
    protected $error_map = [];

    /**
     * Set the current error map we're going to build a representation for.
     *
     * @param array $error_map
     * @return Markdown
     */
    public function setErrorMap(array $error_map = [])
    {
        $this->error_map = $error_map;
        return $this;
    }

    /**
     * Take compiled API documentation and generate a Markdown-based error map over the life of the API.
     *
     * @return array
     */
    public function generate()
    {
        $markdown = [];

        foreach ($this->error_map as $version => $groups) {
            $content = '';

            $api_name = $this->config->getName();
            if (!empty($api_name)) {
                $content .= sprintf('# Errors: %s', $api_name);
                $content .= $this->line(2);
            } else {
                $content .= sprintf('# Errors', $api_name);
                $content .= $this->line(2);
            }

            foreach ($groups as $group => $actions) {
                $content .= sprintf('## %s', $group);
                $content .= $this->line(1);

                $content .= '| URI | Method | HTTP Code | Error Code | Description |';
                $content .= $this->line(1);
                $content .= '| :--- | :--- | :--- | :--- | :--- |';
                $content .= $this->line(1);

                foreach ($actions as $errors) {
                    foreach ($errors as $error) {
                        $content .= sprintf(
                            '| `%s` | %s | %s | %s | %s |',
                            $error['uri'],
                            $error['method'],
                            $error['http_code'],
                            $error['error_code'],
                            $error['description']
                        );

                        $content .= $this->line(1);
                    }
                }

                $content .= $this->line(2);
            }

            $content = trim($content);

            $markdown[$version] = $content;
        }

        return $markdown;
    }
}
