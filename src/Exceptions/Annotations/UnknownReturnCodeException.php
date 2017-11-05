<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class UnknownReturnCodeException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $annotation,
        string $docblock,
        string $class,
        string $method
    ): UnknownReturnCodeException {
        $message = sprintf(
            'Could not find a code for `@api-%s %s` in %s::%s.',
            $annotation,
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
