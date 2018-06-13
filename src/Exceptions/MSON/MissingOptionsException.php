<?php
namespace Mill\Exceptions\MSON;

use Mill\Exceptions\BaseException;

class MissingOptionsException extends BaseException
{
    /** @var null|string */
    public $type = null;

    /**
     * @param string $type
     * @param string $class
     * @param string $method
     * @return MissingOptionsException
     */
    public static function create(string $type, string $class, string $method): MissingOptionsException
    {
        $message = sprintf(
            'A MSON type of `%s` in %s::%s requires accompanying acceptable values.',
            $type,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->type = $type;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }

    /**
     * Get the type that this response exception was triggered for.
     *
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }
}
