<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Parser\Annotation;

/**
 * Handler for the `@api-uri` annotation.
 *
 */
class UriAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_VERSIONING = false;
    const SUPPORTS_DEPRECATION = true;

    const GROUP_REGEX = '/{([\w\/\\\ ]+)}/';

    /**
     * Group that this URI belongs to. Optional.
     *
     * @var string
     */
    protected $group;

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
        'group',
        'path',
        'visible'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     */
    protected function parser()
    {
        $parsed = [];
        $content = $this->docblock;

        // Group is surrounded by `{curly braces}`.
        if (preg_match(self::GROUP_REGEX, $content, $matches)) {
            $parsed['group'] = $matches[1];
            $content = trim(preg_replace(self::GROUP_REGEX, '', $content));
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
    protected function interpreter()
    {
        $this->group = $this->required('group');
        $this->path = $this->required('path');
    }

    /**
     * Get the URI path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get a "cleansed" version of the URI path.
     *
     * @return string
     */
    public function getCleanPath()
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
