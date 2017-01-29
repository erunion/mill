<?php
namespace Mill\Exceptions;

trait ExceptionTrait
{
    /**
     * @var string
     */
    public $class;

    /**
     * @var string|null
     */
    public $method = null;

    /**
     * @var string|null
     */
    public $annotation = null;

    /**
     * Get the class that this exception occurred in.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get the class method that this exception occurred in.
     *
     * @return string|null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the name of the annotation that this exception is for.
     *
     * @return string|null
     */
    public function getAnnotation()
    {
        return $this->annotation;
    }
}
