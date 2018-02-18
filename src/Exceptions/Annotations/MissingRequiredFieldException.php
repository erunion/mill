<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class MissingRequiredFieldException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $required_field,
        string $annotation,
        Docblock $docblock
    ): MissingRequiredFieldException {
        $message = sprintf(
            'You must add a `%s` to the `@api-%s` annotation on line %s in %s.',
            $required_field,
            $annotation,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->required_field = $required_field;
        $exception->annotation = $annotation;
        $exception->docblock = $docblock;

        return $exception;
    }
}
