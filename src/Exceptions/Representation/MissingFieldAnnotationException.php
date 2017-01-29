<?php
namespace Mill\Exceptions\Representation;

class MissingFieldAnnotationException extends \Exception
{
    use RepresentationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string $method
     * @return MissingFieldAnnotationException
     */
    public static function create($annotation, $class, $method)
    {
        $message = sprintf(
            'Missing `@api-%s` annotation on %s::%s',
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
