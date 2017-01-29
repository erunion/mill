<?php
namespace Mill\Exceptions\Resource\Annotations;

use Mill\Exceptions\Resource\ResourceExceptionTrait;

trait AnnotationExceptionTrait
{
    use ResourceExceptionTrait;

    /**
     * @var string|null
     */
    public $docblock = null;

    /**
     * @var string|null
     */
    public $required_field = null;

    /**
     * @var array
     */
    public $values = [];

    /**
     * Get the raw docblock for this annotation.
     *
     * @return string|null
     */
    public function getDocblock()
    {
        return $this->docblock;
    }

    /**
     * Get the required field that this annotation is missing.
     *
     * @return string|null
     */
    public function getRequiredField()
    {
        return $this->required_field;
    }

    /**
     * Get the array of values that this exception allows.
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}
