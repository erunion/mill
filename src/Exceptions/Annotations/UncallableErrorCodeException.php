<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class UncallableErrorCodeException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(string $docblock, string $class, string $method): UncallableErrorCodeException
    {
        $message = sprintf(
            'The `@api-error %s` in %s::%s has an uncallable error code.',
            $docblock,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->docblock = $docblock;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
