<?php
namespace Mill\Exceptions\Resource;

class TooManyAliasedUrisException extends \Exception
{
    use ResourceExceptionTrait;

    /**
     * @param string $class
     * @param string|null $method
     * @return TooManyAliasedUrisException
     */
    public static function create($class, $method)
    {
        $message = sprintf(
            'In %s::%s, you have too many URI aliases set. If you have an alias present, there must be exactly one ' .
                'that is not.',
            $class,
            $method
        );

        $exception = new self($message);
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
