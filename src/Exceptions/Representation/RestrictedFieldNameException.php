<?php
namespace Mill\Exceptions\Representation;

use Mill\Application;
use Mill\Exceptions\BaseException;

class RestrictedFieldNameException extends BaseException
{
    use RepresentationExceptionTrait;

    /**
     * @param string $class
     * @param null|string $method
     * @return RestrictedFieldNameException
     */
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
