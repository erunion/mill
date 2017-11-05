<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class MultipleAnnotationsException extends BaseException
{
    use AnnotationExceptionTrait;

    public static function create(
        string $annotation,
        string $class,
        string $method = null
    ): MultipleAnnotationsException {
        $message = sprintf(
            'Multiple `@api-%s` annotations were found on %s%s. Only one is permissible.',
            $annotation,
            $class,
            (!empty($method)) ? '::' . $method : null
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
