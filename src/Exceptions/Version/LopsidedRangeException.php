<?php
namespace Mill\Exceptions\Version;

class LopsidedRangeException extends \Exception
{
    use VersionExceptionTrait;

    /**
     * @param string $version
     * @param string $class
     * @param string $method
     * @return LopsidedRangeException
     */
    public static function create($version, $class, $method)
    {
        $message = sprintf(
            'You have a lopsided `@api-version` range `%s` in %s::%s.',
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
        return sprintf('The supplied version, `%s`, is lopsided.', $this->version);
    }
}
