<?php
namespace Mill\Exceptions\Resource;

class UnsupportedDecoratorException extends \Exception
{
    use ResourceExceptionTrait;

    /**
     * @param string $decorator
     * @param string $annotation
     * @param string $class
     * @param string $method
     * @return UnsupportedDecoratorException
     */
    public static function create($decorator, $annotation, $class, $method)
    {
        $message = sprintf(
            'An unsupported decorator, `%s`, was found on `@api-%s` in %s::%s.',
            $decorator,
            $annotation,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->decorator = $decorator;
        $exception->annotation = $annotation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
