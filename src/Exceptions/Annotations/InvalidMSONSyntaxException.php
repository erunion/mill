<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class InvalidMSONSyntaxException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $required_field,
        string $annotation,
        string $docblock,
        string $class,
        string $method
    ): InvalidMSONSyntaxException {
        $message = sprintf(
            'Unable to parse a `%s` in the MSON on %s::%s for: `@api-%s %s`',
            $required_field,
            $class,
            $method,
            $annotation,
            $docblock
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
