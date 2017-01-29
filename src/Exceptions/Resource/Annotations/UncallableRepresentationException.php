<?php
namespace Mill\Exceptions\Resource\Annotations;

class UncallableRepresentationException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $docblock
     * @param string $class
     * @param string $method
     * @return UncallableRepresentationException
     */
    public static function create($annotation, $docblock, $class, $method)
    {
        $message = sprintf(
            'The `@api-%s %s` in %s::%s has an uncallable representation. Is it missing a global namespace reference?',
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
