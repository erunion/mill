<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class AbsoluteVersionException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $version_type,
        string $annotation,
        string $class,
        string $method
    ): AbsoluteVersionException {
        $message = sprintf(
            'The version on `@api-%sversion %s` in %s::%s is not an absolute version.',
            $version_type,
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
