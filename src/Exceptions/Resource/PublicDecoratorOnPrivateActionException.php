<?php
namespace Mill\Exceptions\Resource;

use Mill\Exceptions\BaseException;

class PublicDecoratorOnPrivateActionException extends BaseException
{
    use ResourceExceptionTrait;

    public static function create(
        string $annotation,
        string $class,
        string $method
    ): PublicDecoratorOnPrivateActionException {
        $message = sprintf(
            'An `@api-%s` annotation in %s::%s has a `:public` decorator, but is on a private endpoint.',
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
