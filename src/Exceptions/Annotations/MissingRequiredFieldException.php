<?php
namespace Mill\Exceptions\Annotations;

class MissingRequiredFieldException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $required_field
     * @param string $annotation
     * @param string $docblock
     * @param string $class
     * @param string $method
     * @return MissingRequiredFieldException
     */
    public static function create($required_field, $annotation, $docblock, $class, $method)
    {
        $message = sprintf(
            'You must add a `%s` to `@api-%s %s` in %s::%s.',
            $required_field,
            $annotation,
            $docblock,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->required_field = $required_field;
        $exception->annotation = $annotation;
        $exception->docblock = $docblock;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
