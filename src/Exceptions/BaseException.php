<?php
namespace Mill\Exceptions;

class BaseException extends \Exception
{
    /** @var string */
    public $class;

    /** @var null|string */
    public $method = null;

    /** @var null|string */
    public $annotation = null;

    /**
     * Get the class that this exception occurred in.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the class method that this exception occurred in.
     *
     * @return null|string
     */
    public function getMethod(): ?string
    {
        return $this->method;
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
