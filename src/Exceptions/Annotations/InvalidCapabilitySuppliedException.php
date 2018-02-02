<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class InvalidCapabilitySuppliedException extends BaseException
{
    use AnnotationExceptionTrait;

    /**
     * @var string
     */
    public $capability;

    public static function create(
        string $capability,
        string $class,
        string $method
    ): InvalidCapabilitySuppliedException {
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
    public function getCapability(): string
    {
        return $this->capability;
    }
}
