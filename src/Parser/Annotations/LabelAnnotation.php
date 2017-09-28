<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-label` annotation.
 *
 */
class LabelAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_DEPRECATION = false;
    const SUPPORTS_VERSIONING = false;

    /**
     * Label
     *
     * @var string
     */
    protected $label;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'label'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     */
    protected function parser()
    {
        return [
            'label' => $this->docblock
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
        $this->label = $this->required('label');
    }

    /**
     * With an array of data that was output from an Annotation, via `toArray()`, hydrate a new Annotation object.
     *
     * @param array $data
     * @param Version|null $version
     * @return self
     */
    public static function hydrate(array $data = [], Version $version = null): self
    {
        /** @var LabelAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setLabel($data['label']);

        return $annotation;
    }

    /**
     * Get the label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param $label
     * @return self
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }
}
