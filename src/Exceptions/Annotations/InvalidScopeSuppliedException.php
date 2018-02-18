<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class InvalidScopeSuppliedException extends BaseException
{
    use AnnotationExceptionTrait;

    /** @var string */
    public $scope;

    public static function create(string $scope, Docblock $docblock): InvalidScopeSuppliedException
    {
        $message = sprintf(
            'The scope on `@api-scope %s` on line %s in %s is not present in your config.',
            $scope,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->scope = $scope;
        $exception->docblock = $docblock;

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
