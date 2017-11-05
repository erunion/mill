<?php
namespace Mill\Exceptions;

class MethodNotSuppliedException extends BaseException
{
    public static function create(string $class): MethodNotSuppliedException
    {
        $message = sprintf(
            'No method was supplied on %s.',
            $class
        );

        $exception = new self($message);
        $exception->class = $class;

        return $exception;
    }
}
