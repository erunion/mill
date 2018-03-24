<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class UnknownErrorRepresentationException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $representation,
        string $class,
        string $method
    ): UnknownErrorRepresentationException {
        $message = sprintf(
            'The `@api-error %s` in %s::%s has an unknown representation. Is it present in your config file?',
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
