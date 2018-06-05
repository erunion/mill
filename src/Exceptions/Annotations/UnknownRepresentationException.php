<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class UnknownRepresentationException extends BaseException
{
    use AnnotationExceptionTrait;

    /**
     * @param string $representation
     * @param string $class
     * @param string $method
     * @return UnknownRepresentationException
     */
    public static function create(
        string $representation,
        string $class,
        string $method
    ): UnknownRepresentationException {
        $message = sprintf(
            'The `@api-return %s` in %s::%s has an unknown representation. Is it present in your config file?',
            $representation,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->docblock = $representation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
