<?php
namespace Mill\Exceptions\Resource;

class PublicDecoratorOnPrivateActionException extends \Exception
{
    use ResourceExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string $method
     * @return PublicDecoratorOnPrivateActionException
     */
    public static function create($annotation, $class, $method)
    {
        $message = sprintf(
            'An `@api-%s` annotation in %s::%s has a `:public` decorator, but is on a private endpoint.',
            $annotation,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
