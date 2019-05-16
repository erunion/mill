<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class RequiredAnnotationException extends BaseException
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string|null $method
     * @return RequiredAnnotationException
     */
    public static function create(string $annotation, string $class, string $method = null): RequiredAnnotationException
    {
        $message = sprintf(
            'A required annotation, `@api-%s`, is missing from %s%s.',
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
