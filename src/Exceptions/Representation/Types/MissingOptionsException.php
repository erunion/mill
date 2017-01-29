<?php
namespace Mill\Exceptions\Representation\Types;

class MissingOptionsException extends \Exception
{
    use TypeExceptionTrait;

    /**
     * @param string $type
     * @param string $class
     * @param string $method
     * @return MissingOptionsException
     */
    public static function create($type, $class, $method)
    {
        $message = sprintf(
            'Am `@api-type`, `%s`, requires `@api-options`, but none were found in %s::%s.',
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
