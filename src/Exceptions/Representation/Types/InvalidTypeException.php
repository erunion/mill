<?php
namespace Mill\Exceptions\Representation\Types;

class InvalidTypeException extends \Exception
{
    use TypeExceptionTrait;

    /**
     * @param string $type
     * @param string $class
     * @param string $method
     * @return InvalidTypeException
     */
    public static function create($type, $class, $method)
    {
        $message = sprintf(
            'An invalid `@api-type`, `%s`, has been found in %s::%s.',
            $type,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->type = $type;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
