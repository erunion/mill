<?php
namespace Mill\Exceptions;

class MethodNotSuppliedException extends \Exception
{
    use ExceptionTrait;

    /**
     * @param string $class
     * @return MethodNotSuppliedException
     */
    public static function create($class)
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
