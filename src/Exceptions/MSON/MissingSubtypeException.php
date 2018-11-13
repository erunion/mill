<?php
namespace Mill\Exceptions\MSON;

use Mill\Exceptions\BaseException;

class MissingSubtypeException extends BaseException
{
    /** @var string */
    public $annotation;

    /**
     * @param string $annotation
     * @param string $class
     * @param string $method
     * @return MissingSubtypeException
     */
    public static function create(string $annotation, string $class, string $method): MissingSubtypeException
    {
        $message = sprintf(
            'A MSON type of `array` on `%s` in %s::%s requires an accompanying subtype.',
            $annotation,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }

    /**
     * Get the annotation that this MSON exception was triggered for.
     *
     * @return null|string
     */
    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }
}
