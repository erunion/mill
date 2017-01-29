<?php
namespace Mill\Exceptions;

class InvalidCapabilitySuppliedException extends \Exception
{
    use ExceptionTrait;

    /**
     * @var string
     */
    public $capability;

    /**
     * @param string $capability
     * @param string $class
     * @param string $method
     * @return InvalidCapabilitySuppliedException
     */
    public static function create($capability, $class, $method)
    {
        $message = sprintf(
            'The capability on `@api-capability %s` in %s::%s is not present in your config.',
            $capability,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->capability = $capability;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
