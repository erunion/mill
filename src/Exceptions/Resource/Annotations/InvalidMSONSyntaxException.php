<?php
namespace Mill\Exceptions\Resource\Annotations;

class InvalidMSONSyntaxException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $docblock
     * @param string $class
     * @param string $method
     * @return InvalidMSONSyntaxException
     */
    public static function create($required_field, $annotation, $docblock, $class, $method)
    {
        $message = sprintf(
            'Unable to parse a `%s` in the MSON on %s::%s for: `@api-%s %s`',
            $required_field,
            $class,
            $method,
            $annotation,
            $docblock
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->docblock = $docblock;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
