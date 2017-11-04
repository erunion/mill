<?php
namespace Mill\Exceptions\Representation;

trait RepresentationExceptionTrait
{
    /** @var null|string */
    public $field = null;

    /**
     * Get the field that this response exception was triggered for.
     *
     * @return null|string
     */
    public function getField(): ?string
    {
        return $this->field;
    }
}
