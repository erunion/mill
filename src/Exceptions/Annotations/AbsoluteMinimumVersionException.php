<?php
namespace Mill\Exceptions\Annotations;

class AbsoluteMinimumVersionException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string $method
     * @return AbsoluteMinimumVersionException
     */
    public static function create($annotation, $class, $method)
    {
        $message = sprintf(
            'The version on `@api-minVersion %s` in %s::%s is not an absolute version.',
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
