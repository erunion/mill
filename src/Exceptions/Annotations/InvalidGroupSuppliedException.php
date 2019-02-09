<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class InvalidGroupSuppliedException extends BaseException
{
    use AnnotationExceptionTrait;

    /** @var string */
    public $group;

    /**
     * @param string $group
     * @param string $class
     * @param string $method
     * @return InvalidGroupSuppliedException
     */
    public static function create(
        string $group,
        string $class,
        string $method
    ): self {
        $message = sprintf(
            'The group on `@api-group %s` in %s::%s is not present in your config.',
            $group,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->group = $group;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }

    /**
     * Get the vendor tag that this exception occurred with.
     *
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }
}
