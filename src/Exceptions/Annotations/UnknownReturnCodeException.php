<?php
namespace Mill\Exceptions\Annotations;

class UnknownReturnCodeException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $docblock
     * @param string $class
     * @param string $method
     * @return UnknownReturnCodeException
     */
    public static function create($annotation, $docblock, $class, $method)
    {
        $message = sprintf(
            'Could not find a code for `@api-%s %s` in %s::%s.',
            $annotation,
            $docblock,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->docblock = $docblock;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
