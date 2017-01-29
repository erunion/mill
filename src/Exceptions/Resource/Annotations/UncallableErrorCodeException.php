<?php
namespace Mill\Exceptions\Resource\Annotations;

class UncallableErrorCodeException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $docblock
     * @param string $class
     * @param string $method
     * @return UncallableErrorCodeException
     */
    public static function create($docblock, $class, $method)
    {
        $message = sprintf(
            'The `@api-throws %s` in %s::%s has an uncallable error code.',
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
