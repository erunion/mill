<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Parser\Annotation;

class PathAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_DEPRECATION = true;

    const ARRAYABLE = [
        'path'
    ];

    /** @var string URI path that this annotation represents. */
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
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return PathAnnotation
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

        // If we have any path param translations configured, let's process them.
        $translations = Container::getConfig()->getPathParamTranslations();
        foreach ($translations as $from => $to) {
            $path = str_replace('{' . $from . '}', '{' . $to . '}', $path);
        }

        return $path;
    }

    /**
     * @param PathParamAnnotation $param
     * @return bool
     */
    public function doesPathHaveParam(PathParamAnnotation $param): bool
    {
        return strpos($this->getCleanPath(), '{' . $param->getField() . '}') !== false;
    }

    /**
     * {{@inheritdoc}}
     */
    public function toArray(): array
    {
        $arr = parent::toArray();
        $arr['aliased'] = $this->isAliased();
        $arr['aliases'] = [];

        /** @var Annotation $alias */
        foreach ($this->getAliases() as $alias) {
            $arr['aliases'][] = $alias->toArray();
        }

        ksort($arr);

        return $arr;
    }
}
