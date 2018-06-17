<?php
namespace Mill\Exceptions\Resource;

use Mill\Exceptions\BaseException;

class UnsupportedDecoratorException extends BaseException
{
    use ResourceExceptionTrait;

    /**
     * @param string $decorator
     * @param string $annotation
     * @param string $class
     * @param string $method
     * @return UnsupportedDecoratorException
     */
    public static function create(
        string $decorator,
        string $annotation,
        string $class,
        string $method
    ): UnsupportedDecoratorException {
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
