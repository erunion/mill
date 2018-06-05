<?php
namespace Mill\Exceptions\Representation;

use Mill\Exceptions\BaseException;

class DuplicateFieldException extends BaseException
{
    use RepresentationExceptionTrait;

    /**
     * @param string $field
     * @param string $class
     * @param string $method
     * @return DuplicateFieldException
     */
    public static function create(string $field, string $class, string $method): DuplicateFieldException
    {
        $message = sprintf(
            '`%s` has been found twice in %s::%s. This is not allowed.',
            $field,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->field = $field;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
