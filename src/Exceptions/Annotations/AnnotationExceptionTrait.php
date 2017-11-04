<?php
namespace Mill\Exceptions\Annotations;

trait AnnotationExceptionTrait
{
    /** @var null|string */
    public $docblock = null;

    /** @var null|string */
    public $required_field = null;

    /** @var array */
    public $values = [];

    /**
     * Get the raw docblock for this annotation.
     *
     * @return null|string
     */
    public function getDocblock(): ?string
    {
        return $this->docblock;
    }

    /**
     * Get the required field that this annotation is missing.
     *
     * @return null|string
     */
    public function getRequiredField(): ?string
    {
        return $this->required_field;
    }

    /**
     * Get the array of values that this exception allows.
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
