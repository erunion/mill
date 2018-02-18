<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class UnknownReturnCodeException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $annotation,
        string $content,
        Docblock $docblock
    ): UnknownReturnCodeException {
        $message = sprintf(
            'Could not find a code for `@api-%s %s` on line %s in %s.',
            $annotation,
            $content,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->content = $content;
        $exception->docblock = $docblock;

        return $exception;
    }
}
