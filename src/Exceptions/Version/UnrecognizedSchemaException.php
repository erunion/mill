<?php
namespace Mill\Exceptions\Version;

use Mill\Exceptions\ExceptionTrait;

class UnrecognizedSchemaException extends \Exception
{
    use ExceptionTrait;

    /**
     * @var string|null
     */
    public $version = null;

    /**
     * @param string $version
     * @param string $class
     * @param string $method
     * @return UnrecognizedSchemaException
     */
    public static function create($version, $class, $method)
    {
        $message = sprintf(
            'A `@api-version` annotation in %s::%s was found with an unrecognized schema of `%s`.',
            $class,
            $method,
            $version
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
            'The supplied version, `%s`, has an unrecognized schema. Please consult the versioning documentation.',
            $this->version
        );
    }

    /**
     * Get the version that an annotation exception was triggered for.
     *
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }
}
