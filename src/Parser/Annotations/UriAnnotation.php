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
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'namespace',
        'path'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
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
     * Interpret the parsed annotation data and set local variables to build the annotation.
     *
     * To facilitate better error messaging, the order in which items are interpreted here should be match the schema
     * of the annotation.
     *
     * @return void
     */
    protected function interpreter(): void
    {
        $this->namespace = $this->required('namespace');
        $this->path = $this->required('path');
    }

    /**
     * With an array of data that was output from an Annotation, via `toArray()`, hydrate a new Annotation object.
     *
     * @param array $data
     * @param null|Version $version
     * @return self
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
     * Get the namespace that this URI belongs to.
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Set the namespace that this URI belongs to.
     *
     * @param string $namespace
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Get the URI path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the URI path.
     *
     * @param string $path
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get a "cleansed" version of the URI path.
     *
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
