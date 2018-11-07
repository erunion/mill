<?php
namespace Mill\Compiler\ErrorMap\Formats;

use Mill\Compiler;
use Mill\Compiler\Traits;

class Markdown extends Compiler\ErrorMap
{
    use Traits\Markdown;

    /** @var array */
    protected $markdown = [];

    /**
     * Take compiled API documentation and convert it into a Markdown-based error map over the life of the API.
     *
     */
    public function compile(): void
    {
        parent::compile();

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

                $content .= '| Error Code | Path | Method | HTTP Code | Description |';
                $content .= $this->line(1);
                $content .= '| :--- | :--- | :--- | :--- | :--- |';
                $content .= $this->line(1);

                foreach ($actions as $errors) {
                    foreach ($errors as $error) {
                        $content .= sprintf(
                            '| %s | %s | %s | %s | %s |',
                            $error['error_code'],
                            $error['path'],
                            $error['method'],
                            $error['http_code'],
                            $error['description']
                        );

                        $content .= $this->line(1);
                    }
                }

                $content .= $this->line(1);
            }

            $this->markdown[$version] = trim($content);
        }
    }

    /**
     * @return array
     */
    public function getCompiled(): array
    {
        if (empty($this->markdown)) {
            $this->compile();
        }

        return $this->markdown;
    }
}
