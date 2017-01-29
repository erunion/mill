<?php
namespace Mill\Exceptions\Representation;

class DuplicateAnnotationsOnFieldException extends \Exception
{
    use RepresentationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string $method
     * @return DuplicateAnnotationsOnFieldException
     */
    public static function create($annotation, $class, $method)
    {
        $message = sprintf(
            'Multiple `@api-%s` annotations were found on the same field in %s::%s. Only one is permissible.',
            $annotation,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
