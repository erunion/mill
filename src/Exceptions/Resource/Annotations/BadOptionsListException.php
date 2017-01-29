<?php
namespace Mill\Exceptions\Resource\Annotations;

class BadOptionsListException extends \Exception
{
    use AnnotationExceptionTrait;

    /**
     * @param string $annotation
     * @param array $values
     * @param string $class
     * @param string $method
     * @return BadOptionsListException
     */
    public static function create($annotation, array $values, $class, $method)
    {
        $message = sprintf(
            'The options list on `@api-param %s`in %s::%s should written as `[%s]`, not `[%s]`.',
            $annotation,
            $class,
            $method,
            preg_replace("/,( )?/uim", "|", implode(',', $values)),
            implode(',', $values)
        );

        $exception = new self($message);
        $exception->annotation = $annotation;
        $exception->values = $values;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }
}
