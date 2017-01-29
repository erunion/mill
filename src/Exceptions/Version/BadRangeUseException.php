<?php
namespace Mill\Exceptions\Version;

class BadRangeUseException extends \Exception
{
    use VersionExceptionTrait;

    /**
     * @param string $version
     * @param string $proper
     * @param string $class
     * @param string $method
     * @return BadRangeUseException
     */
    public static function create($version, $proper, $class, $method)
    {
        $message = sprintf(
            'You have a `@api-version` range, %s, in %s::%s, that would be better off written as: %s',
            $version,
            $class,
            $method,
            $proper
        );

        $exception = new self($message);
        $exception->version = $version;
        $exception->proper_version = $proper;
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
            'The supplied version, `%s`, should be written as `%s`.',
            $this->version,
            $this->proper_version
        );
    }
}
