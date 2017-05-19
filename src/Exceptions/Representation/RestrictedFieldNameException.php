<?php
namespace Mill\Exceptions\Representation;

use Mill\Parser\Representation\Documentation;

class RestrictedFieldNameException extends \Exception
{
    use RepresentationExceptionTrait;

    /**
     * @param string $class
     * @param string $method
     * @return RestrictedFieldNameException
     */
    public static function create($class, $method)
    {
        $message = sprintf(
            '`%s` is a reserved `@api-field` name, and cannot be used in %s::%s.',
            Documentation::DOT_NOTATION_ANNOTATION_DATA_KEY,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
