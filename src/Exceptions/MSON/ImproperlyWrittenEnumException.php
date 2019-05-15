<?php
namespace Mill\Exceptions\MSON;

use Mill\Exceptions\Annotations\AnnotationExceptionTrait;
use Mill\Exceptions\BaseException;

class ImproperlyWrittenEnumException extends BaseException
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param null|string $method
     * @return ImproperlyWrittenEnumException
     */
    public static function create(string $annotation, string $class, ?string $method): ImproperlyWrittenEnumException
    {
        $message = sprintf(
            'The type on `%s` in %s%s should be written as `enum`.',
            $annotation,
            $class,
            (!is_null($method)) ? sprintf('::%s', $method) : ''
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
