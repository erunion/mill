<?php
namespace Mill\Generator;

use Mill\Generator;
use Mill\Generator\ErrorMap\Formats\Markdown;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Resource\Action;

class ErrorMap extends Generator
{
    const FORMAT_MARKDOWN = 'markdown';

    /**
     * Generated error map.
     *
     * @var array
     */
    protected $error_map = [];

    /**
     * Take compiled API documentation and generate an error map over the life of the API.
     *
     * @return array
     */
    public function generate()
    {
        parent::generate();

        foreach ($this->getResources() as $version => $resources) {
            foreach ($resources as $group_name => $data) {
                // Groups can have children via the `\` delimiter, but for the error map generator we only care about
                // the top-level group name.
                if (strpos($group_name, '\\') != false) {
                    $parts = explode('\\', $group_name);
                    $group_name = array_shift($parts);
                }

                foreach ($data['resources'] as $resource_name => $resource) {
                    /** @var Action\Documentation $action */
                    foreach ($resource['actions'] as $identifier => $action) {
                        /** @var ReturnAnnotation|ThrowsAnnotation $response */
                        foreach ($action->getResponses() as $response) {
                            if (!$response instanceof ThrowsAnnotation) {
                                continue;
                            }

                            $error_code = $response->getErrorCode();
                            if (empty($error_code)) {
                                continue;
                            }

                            $uri = $action->getUri();
                            $this->error_map[$version][$group_name][$identifier][] = [
                                'uri' => $uri->getCleanPath(),
                                'method' => $action->getMethod(),
                                'http_code' => $response->getHttpCode(),
                                'error_code' => $error_code,
                                'description' => $response->getDescription()
                            ];
                        }
                    }
                }
            }
        }

        // Keep things tidy
        foreach ($this->error_map as $version => $groups) {
            foreach ($groups as $group => $resources) {
                foreach ($resources as $identifier => $errors) {
                    usort($this->error_map[$version][$group][$identifier], function ($a, $b) {
                        if ($a['error_code'] == $b['error_code']) {
                            return 0;
                        }

                        return ($a['error_code'] < $b['error_code']) ? -1 : 1;
                    });
                }

                ksort($this->error_map[$version][$group]);
            }
        }

        return $this->error_map;
    }

    /**
     * Take compiled API documentation and generate a Markdown-based changelog over the life of the API.
     *
     * @return array
     */
    public function generateMarkdown()
    {
        $markdown = new Markdown($this->config);
        $markdown->setErrorMap($this->generate());
        return $markdown->generate();
    }
}
