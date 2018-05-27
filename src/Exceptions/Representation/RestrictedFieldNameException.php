<?php
namespace Mill\Exceptions\Representation;

use Mill\Application;
use Mill\Exceptions\BaseException;
use Mill\Parser\Representation\Documentation;

class RestrictedFieldNameException extends BaseException
{
    use RepresentationExceptionTrait;

    public static function create(string $class, ?string $method): RestrictedFieldNameException
    {
        $message = sprintf(
            '`%s` is a reserved `@api-field` name, and cannot be used in %s::%s.',
            Application::DOT_NOTATION_ANNOTATION_DATA_KEY,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
