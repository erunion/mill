<?php
namespace Mill\Exceptions\Representation;

use Mill\Exceptions\ExceptionTrait;

trait RepresentationExceptionTrait
{
    use ExceptionTrait;

    /**
     * @var string|null
     */
    public $field = null;

    /**
     * Get the field that this response exception was triggered for.
     *
     * @return string|null
     */
    public function getField()
    {
        return $this->field;
    }
}
