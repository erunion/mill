<?php
namespace Mill\Exceptions\Resource;

class NoAnnotationsException extends \Exception
{
    use ResourceExceptionTrait;

    /**
     * @param string $class
     * @param string|null $method
     * @return NoAnnotationsException
     */
    public static function create($class, $method)
    {
        if (empty($method)) {
            $message = sprintf('No annotations could be found on %s, does it have a docblock?', $class);
        } else {
            $message = sprintf('No annotations could be found on %s::%s, does it have a docblock?', $class, $method);
        }

        $exception = new self($message);
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
