<?php
namespace Mill\Exceptions\Annotations;

class RequiredAnnotationException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string|null $method
     * @return RequiredAnnotationException
     */
    public static function create($annotation, $class, $method = null)
    {
        $message = sprintf(
            'A required annotation, `@api-%s`, is missing from %s%s.',
            $annotation,
            $class,
            (!empty($method)) ? '::' . $method : null
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
