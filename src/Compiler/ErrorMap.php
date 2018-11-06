<?php
namespace Mill\Compiler;

use Mill\Compiler;
use Mill\Compiler\ErrorMap\Formats\Markdown;
use Mill\Parser\Annotations\ErrorAnnotation;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Resource\Action;

class ErrorMap extends Compiler
{
    /** @var array Compiled error map. */
    protected $error_map = [];

    /**
     * Take compiled API documentation and compile an error map over the life of the API.
     *
     * @throws \Exception
     */
    public function compile(): void
    {
        parent::compile();

        foreach ($this->getResources() as $version => $resources) {
            foreach ($resources as $group => $data) {
                // Groups can have children via the `\` delimiter, but for the error map we only care about the
                // top-level group.
                if (strpos($group, '\\') != false) {
                    $parts = explode('\\', $group);
                    $group = array_shift($parts);
                }

                /** @var Action\Documentation $action */
                foreach ($data['actions'] as $identifier => $action) {
                    /** @var ReturnAnnotation|ErrorAnnotation $response */
                    foreach ($action->getResponses() as $response) {
                        if (!$response instanceof ErrorAnnotation) {
                            continue;
                        }

                        $error_code = $response->getErrorCode();
                        if (empty($error_code)) {
                            continue;
                        }

                        $path = $action->getPath();
                        $this->error_map[$version][$group][$error_code][] = [
                            'path' => $path->getCleanPath(),
                            'method' => $action->getMethod(),
                            'http_code' => $response->getHttpCode(),
                            'error_code' => $error_code,
                            'description' => $response->getDescription()
                        ];
                    }
                }
            }
        }

        foreach ($this->error_map as $version => $groups) {
            foreach ($groups as $group => $resources) {
                foreach ($resources as $identifier => $errors) {
                    usort($this->error_map[$version][$group][$identifier], function (array $a, array $b): int {
                        // If the error codes match, then fallback to sorting by the path.
                        if ($a['error_code'] == $b['error_code']) {
                            // If the paths match, then fallback to sorting by their methods.
                            if ($a['path'] == $b['path']) {
                                return ($a['method'] < $b['method']) ? -1 : 1;
                            }

                            return ($a['path'] < $b['path']) ? -1 : 1;
                        }

                        return ($a['error_code'] < $b['error_code']) ? -1 : 1;
                    });
                }

                ksort($this->error_map[$version][$group]);
            }
        }
    }

    /**
     * @return array
     */
    public function getCompiled(): array
    {
        if (empty($this->error_map)) {
            $this->compile();
        }

        return $this->error_map;
    }

    /**
     * Take compiled API documentation and convert it into a Markdown-based changelog over the life of the API.
     *
     * @return array
     * @throws \Exception
     */
    public function toMarkdown(): array
    {
        $markdown = new Markdown($this->application, $this->version);
        $markdown->setLoadPrivateDocs($this->load_private_docs);
        $markdown->setLoadVendorTagDocs($this->load_vendor_tag_docs);

        return $markdown->getCompiled();
    }
}
