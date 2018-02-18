<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class UnknownErrorRepresentationException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(string $representation, Docblock $docblock): UnknownErrorRepresentationException {
        $message = sprintf(
            'The `@api-throws %s` on line %s in %s has an unknown representation. Is it present in your config file?',
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
