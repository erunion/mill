<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for descriptions.
 *
 */
class DescriptionAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_DEPRECATION = false;
    const SUPPORTS_VERSIONING = false;

    /**
     * Description.
     *
     * @var string
     */
    protected $description;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'description'
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
            'description' => $this->docblock
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
        $this->description = $this->required('description');
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
        /** @var DescriptionAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setDescription($data['description']);

        return $annotation;
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
