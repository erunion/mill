<?php
namespace Mill\Exceptions\Representation\Types;

use Mill\Exceptions\Representation\RepresentationExceptionTrait;

trait TypeExceptionTrait
{
    use RepresentationExceptionTrait;

    /**
     * @var string|null
     */
    public $type = null;

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
