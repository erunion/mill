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

    const NAMESPACE_REGEX = '/{([\w\/\\\ ]+)}/';

    /**
     * Namespace that this URI belongs to.
     *
     * @var string
     */
    protected $namespace;

    /**
     * URI path that this annotation represents.
     *
     * @var string
     */
    protected $path;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'namespace',
        'path'
    ];

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        $parsed = [];
        $content = $this->docblock;

        // Namespace is surrounded by `{curly braces}`.
        if (preg_match(self::NAMESPACE_REGEX, $content, $matches)) {
            $parsed['namespace'] = $matches[1];
            $content = trim(preg_replace(self::NAMESPACE_REGEX, '', $content));
        }

        $parsed['path'] = trim($content);

        return $parsed;
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->namespace = $this->required('namespace');
        $this->path = $this->required('path');
    }

    /**
     * {@inheritdoc}
     */
    public static function hydrate(array $data = [], Version $version = null)
    {
        /** @var UriAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setNamespace($data['namespace']);
        $annotation->setPath($data['path']);

        return $annotation;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
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
