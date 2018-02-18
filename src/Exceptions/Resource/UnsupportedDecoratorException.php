<?php
namespace Mill\Exceptions\Resource;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class UnsupportedDecoratorException extends BaseException
{
    use ResourceExceptionTrait;

    public static function create(
        string $decorator,
        string $annotation,
        Docblock $docblock
    ): UnsupportedDecoratorException {
        $message = sprintf(
            'An unsupported decorator, `%s`, was found on `@api-%s` on line %s in %s.',
            $decorator,
            $annotation,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->decorator = $decorator;
        $exception->annotation = $annotation;
        $exception->docblock = $docblock;

        return $exception;
    }
}
