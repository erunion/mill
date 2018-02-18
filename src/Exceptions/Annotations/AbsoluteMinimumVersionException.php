<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class AbsoluteMinimumVersionException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(string $annotation, Docblock $docblock): AbsoluteMinimumVersionException {
        $message = sprintf(
            'The version on `@api-minVersion %s` on line %s in %s is not an absolute version.',
            $annotation,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->docblock = $docblock;

        return $exception;
    }
}
