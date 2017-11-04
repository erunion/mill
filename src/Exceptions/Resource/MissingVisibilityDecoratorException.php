<?php
namespace Mill\Exceptions\Resource;

use Mill\Exceptions\BaseException;

class MissingVisibilityDecoratorException extends BaseException
{
    use ResourceExceptionTrait;

    public static function create(
        string $annotation,
        string $class,
        string $method
    ): MissingVisibilityDecoratorException {
        $message = sprintf(
            'An `@api-%s` annotation in %s::%s, is missing a visibility decorator.',
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
