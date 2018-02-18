<?php
namespace Mill\Exceptions;

use Mill\Parser\Reader\Docblock;

class BaseException extends \Exception
{
    /** @var Docblock */
    public $docblock;

    /** @var null|string */
    public $annotation = null;

    /**
     * Get the docblock that this exception occurred within.
     *
     * @return Docblock
     */
    public function getDocblock(): Docblock
    {
        return $this->docblock;
    }

    /**
     * Get the name of the annotation that this exception is for.
     *
     * @return null|string
     */
    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }
}
