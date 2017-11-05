<?php
namespace Mill\Exceptions;

class MethodNotImplementedException extends BaseException
{
    public static function create(string $class, string $method): MethodNotImplementedException
    {
        $message = sprintf(
            '%s does not implement %s.',
            $class,
            $method
        );

        $exception = new self($message);
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
