<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class MissingRepresentationErrorCodeException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $representation,
        Docblock $docblock
    ): MissingRepresentationErrorCodeException {
        $message = sprintf(
            'The `%s` error representation on `@api-throws %s` on line %s in %s is missing an error code, but is ' .
                'required to have one in your config file.',
            $representation,
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
