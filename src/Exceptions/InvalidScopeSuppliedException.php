<?php
namespace Mill\Exceptions;

class InvalidScopeSuppliedException extends \Exception
{
    use ExceptionTrait;

    /**
     * @var string
     */
    public $scope;

    /**
     * @param string $scope
     * @param string $class
     * @param string $method
     * @return InvalidScopeSuppliedException
     */
    public static function create($scope, $class, $method)
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
    public function getScope()
    {
        return $this->scope;
    }
}
