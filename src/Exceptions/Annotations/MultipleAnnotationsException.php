<?php
namespace Mill\Exceptions\Annotations;

class MultipleAnnotationsException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param string $class
     * @param string|null $method
     * @return MultipleAnnotationsException
     */
    public static function create($annotation, $class, $method = null)
    {
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
