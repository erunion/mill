<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;

/**
 * Handler for the `@api-type` annotation.
 *
 */
class TypeAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;

    // `@api-type` annotations are only ever used in tandem with `@api-field`, so these don't need to support
    // versioning on their own, since the field annotation does already.
    const SUPPORTS_VERSIONING = false;

    const SUPPORTS_DEPRECATION = false;

    /**
     * Field type.
     *
     * @var string
     */
    protected $type;

    /**
     * Type object representing the type that's at hand.
     *
     * @var \Mill\Parser\Representation\Type
     */
    protected $object;

    /**
     * Subtype representing what the type field contains.
     *
     * @var string|boolean
     */
    protected $subtype = false;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'subtype',
        'type'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     */
    protected function parser()
    {
        /** @var \Mill\Parser\Representation\Type object */
        $this->object = $this->extra_data['object'];

        $subtype = null;
        if ($this->object->requiresSubtype() || $this->object->allowsSubtype()) {
            $subtype = trim($this->extra_data['subtype']);
            if (empty($subtype)) {
                $subtype = false;
            }
        }

        return [
            'type' => $this->docblock,
            'subtype' => $subtype
        ];
    }

    /**
     * Interpret the parsed annotation data and set local variables to build the annotation.
     *
     * To facilitate better error messaging, the order in which items are interpreted here should be match the schema
     * of the annotation.
     *
     * @return void
     */
    protected function interpreter()
    {
        $this->type = $this->required('type');

        if ($this->object->requiresSubtype()) {
            $this->subtype = $this->required('subtype');
        } elseif ($this->object->allowsSubtype()) {
            $this->subtype = $this->optional('subtype');
        }
    }

    /**
     * Get the label.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the type object that this type represents.
     *
     * @return \Mill\Parser\Representation\Type
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Get the subtype that represents what this type field contains.
     *
     * @return string|boolean
     */
    public function getSubtype()
    {
        return $this->subtype;
    }
}
