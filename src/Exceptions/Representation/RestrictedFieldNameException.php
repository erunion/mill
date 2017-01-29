<?php
namespace Mill\Exceptions\Representation;

class RestrictedFieldNameException extends \Exception
{
    use RepresentationExceptionTrait;

    /**
     * @param string $class
     * @param string $method
     * @return RestrictedFieldNameException
     */
    public static function create($class, $method)
    {
        $message = sprintf(
            '`__FIELD_DATA__` is a reserved `@api-field` name, and cannot be used in %s::%s.',
            $class,
            $method
        );

        $exception = new self($message);
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
