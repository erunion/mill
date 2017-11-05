<?php
namespace Mill\Exceptions\Resource;

use Mill\Exceptions\BaseException;

class NoAnnotationsException extends BaseException
{
    use ResourceExceptionTrait;

    public static function create(string $class, ?string $method): NoAnnotationsException
    {
        if (empty($method)) {
            $message = sprintf('No annotations could be found on %s, does it have a docblock?', $class);
        } else {
            $message = sprintf('No annotations could be found on %s::%s, does it have a docblock?', $class, $method);
        }

        $exception = new self($message);
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
