<?php
namespace Mill\Exceptions\Resource\Annotations;

class UnsupportedTypeException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string $method
     * @return UnsupportedTypeException
     */
    public static function create($annotation, $class, $method)
    {
        $message = sprintf(
            'The type on `@api-param %s`in %s::%s is unsupported. Please check the documentation for supported types.',
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
