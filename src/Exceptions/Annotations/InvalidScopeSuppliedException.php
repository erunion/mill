<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class InvalidScopeSuppliedException extends BaseException
{
    use AnnotationExceptionTrait;

    /**
     * @var string
     */
    public $scope;

    public static function create(string $scope, string $class, string $method): InvalidScopeSuppliedException
    {
        $message = sprintf(
            'The scope on `@api-scope %s` in %s::%s is not present in your config.',
            $scope,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->scope = $scope;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }

    /**
     * Get the scope that this exception occurred for.
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }
}
