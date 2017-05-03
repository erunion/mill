<?php
namespace Mill\Exceptions\Annotations;

class MissingRepresentationErrorCodeException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $representation
     * @param string $class
     * @param string $method
     * @return MissingRepresentationErrorCodeException
     */
    public static function create($representation, $class, $method)
    {
        $message = sprintf(
            'The `%s` error representation on `@api-throws %s` in %s::%s is missing an error code, but is required ' .
                'to have one in your config file.',
            $representation,
            $representation,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->docblock = $representation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
