<?php
namespace Mill\Exceptions\Representation;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;
use Mill\Parser\Representation\Documentation;

class RestrictedFieldNameException extends BaseException
{
    use RepresentationExceptionTrait;

    public static function create(Docblock $docblock): RestrictedFieldNameException
    {
        $message = sprintf(
            '`%s` is a reserved `@api-field` name, and cannot be used on line %s in %s.',
            Documentation::DOT_NOTATION_ANNOTATION_DATA_KEY,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->docblock = $docblock;

        return $exception;
    }
}
