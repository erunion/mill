<?php
namespace Mill\Exceptions\Version;

class OperatorsWithinRangeException extends \Exception
{
    use VersionExceptionTrait;

    /**
     * @param string $version
     * @param string $class
     * @param string $method
     * @return OperatorsWithinRangeException
     */
    public static function create($version, $class, $method)
    {
        $message = sprintf(
            'You cannot use operators as part of a constraint in a `@api-version` range %s in %s::%s.',
            $version,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->version = $version;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }

    /**
     * Get a clean error message for this exception that can be used in inline-validation use cases.
     *
     * @return string
     */
    public function getValidationMessage()
    {
        return sprintf(
            'The supplied version, `%s`, contains operators as part of a constraint. Please consult the versioning ' .
                'documentation.',
            $this->version
        );
    }
}
