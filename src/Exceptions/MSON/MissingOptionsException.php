<?php
namespace Mill\Exceptions\MSON;

use Mill\Exceptions\ExceptionTrait;

class MissingOptionsException extends \Exception
{
    use ExceptionTrait;

    /**
     * @var string|null
     */
    public $type = null;

    /**
     * @param string $type
     * @param string $class
     * @param string $method
     * @return MissingOptionsException
     */
    public static function create($type, $class, $method)
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
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }
}
