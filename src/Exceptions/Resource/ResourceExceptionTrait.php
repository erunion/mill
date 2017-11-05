<?php
namespace Mill\Exceptions\Resource;

trait ResourceExceptionTrait
{
    /** @var null|string */
    public $decorator = null;

    /**
     * Get the decorator that this resource exception was triggered for.
     *
     * @return null|string
     */
    public function getDecorator(): ?string
    {
        return $this->decorator;
    }
}
