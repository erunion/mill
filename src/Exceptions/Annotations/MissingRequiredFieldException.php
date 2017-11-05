<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class MissingRequiredFieldException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $required_field,
        string $annotation,
        string $docblock,
        string $class,
        string $method
    ): MissingRequiredFieldException {
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
