<?php
namespace Mill\Exceptions\Resource;

use Mill\Exceptions\ExceptionTrait;

trait ResourceExceptionTrait
{
    use ExceptionTrait;

    /**
     * @var string|null
     */
    public $decorator = null;

    /**
     * Get the decorator that this resource exception was triggered for.
     *
     * @return string|null
     */
    public function getDecorator()
    {
        return $this->decorator;
    }
}
