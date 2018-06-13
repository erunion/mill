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
        $path = trim($this->docblock);
        if (!empty($path)) {
        }

        return [
            'path' => $path
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->path = $this->required('path');

        // If we have any path param translations configured, let's process them.
        $translations = Container::getConfig()->getPathParamTranslations();
        foreach ($translations as $from => $to) {
            if (preg_match('/([@#+*!~])' . $from . '(\/|$)/', $this->path, $matches)) {
                $this->path = preg_replace('/([@#+*!~])' . $from . '(\/|$)/', '$1' . $to . '$2', $this->path);
            }
        }
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
        return preg_replace('/[@#+*!~]((\w|_)+)(\/|$)/', '{$1}$3', $this->getPath());
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
