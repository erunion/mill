<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class MultipleAnnotationsException extends BaseException
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string|null $method
     * @return MultipleAnnotationsException
     */
    public static function create(
        string $annotation,
        string $class,
        string $method = null
    ): MultipleAnnotationsException {
        $message = sprintf(
            'Multiple `@api-%s` annotations were found on %s%s. Only one is permissible.',
            $annotation,
            $class,
            (!empty($method)) ? sprintf('::%s', $method) : ''
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
