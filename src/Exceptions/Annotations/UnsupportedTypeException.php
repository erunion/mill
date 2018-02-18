<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class UnsupportedTypeException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(string $annotation, Docblock $docblock): UnsupportedTypeException
    {
        $message = sprintf(
            'The type on `%s` on line %s in %s is unsupported. Please check the documentation for supported types.',
            $annotation,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->content = $annotation;
        $exception->docblock = $docblock;

        return $exception;
    }
}
