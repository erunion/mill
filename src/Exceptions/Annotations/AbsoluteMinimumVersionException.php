<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class AbsoluteMinimumVersionException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $annotation,
        string $class,
        string $method
    ): AbsoluteMinimumVersionException {
        $message = sprintf(
            'The version on `@api-minversion %s` in %s::%s is not an absolute version.',
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
}
