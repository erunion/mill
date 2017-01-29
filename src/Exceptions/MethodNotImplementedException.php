<?php
namespace Mill\Exceptions;

class MethodNotImplementedException extends \Exception
{
    use ExceptionTrait;

    /**
     * @param string $class
     * @param string $method
     * @return MethodNotImplementedException
     */
    public static function create($class, $method)
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
