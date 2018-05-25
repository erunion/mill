<?php
namespace Mill\Exceptions\Resource;

use Mill\Exceptions\BaseException;

class TooManyAliasedPathsException extends BaseException
{
    use ResourceExceptionTrait;

    public static function create(string $class, ?string $method): TooManyAliasedPathsException
    {
        $message = sprintf(
            'In %s::%s, you have too many path aliases set. If you have an alias present, there must be exactly one ' .
                'that is not.',
            $class,
            $method
        );

        $exception = new self($message);
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
