<?php
namespace Mill\Exceptions\Annotations;

use Mill\Parser\Reader\Docblock;

trait AnnotationExceptionTrait
{
    /** @var null|string */
    //public $content = null;

    /** @var null|string */
    public $required_field = null;

    /** @var array */
    //public $values = [];

    /**
     * Get the raw content for this annotation.
     *
     * @return null|string
     */
    /*public function getContent(): ?string
    {
        return $this->content;
    }*

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
    /*public function getValues(): array
    {
        return $this->values;
    }*/
}
