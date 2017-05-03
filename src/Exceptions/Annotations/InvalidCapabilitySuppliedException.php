<?php
namespace Mill\Exceptions\Annotations;

class InvalidCapabilitySuppliedException extends \Exception
{
    use AnnotationExceptionTrait;

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

    /**
     * Get the capability that this exception occurred with.
     *
     * @return string
     */
    public function getCapability()
    {
        return $this->capability;
    }
}
