<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-uri` annotation.
 *
 */
class UriAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_ALIASING = true;
    const SUPPORTS_DEPRECATION = true;

    const ARRAYABLE = [
        'path'
    ];

    /**
     * URI path that this annotation represents.
     *
     * @var string
     */
    protected $path;

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        return [
            'path' => trim($this->docblock)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->path = $this->required('path');
    }

    /**
     * {@inheritdoc}
     */
    public static function hydrate(array $data = [], Version $version = null)
    {
        /** @var UriAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setPath($data['path']);

        return $annotation;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getCleanPath(): string
    {
        $path = preg_replace('/[@#+*!~]((\w|_)+)(\/|$)/', '{$1}$3', $this->getPath());

        // If we have any URI segment translations configured, let's process them.
        $translations = Container::getConfig()->getUriSegmentTranslations();
        foreach ($translations as $from => $to) {
            $path = str_replace('{' . $from . '}', '{' . $to . '}', $path);
        }

        return $path;
    }
}
