<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class UnknownRepresentationException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(string $representation, Docblock $docblock): UnknownRepresentationException {
        $message = sprintf(
            'The `@api-return %s` on line %s in %s has an unknown representation. Is it present in your config file?',
            $representation,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->content = $representation;
        $exception->docblock = $docblock;

        return $exception;
    }
}
