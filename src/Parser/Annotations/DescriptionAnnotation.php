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
    /** @var string */
    protected $description;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'description'
    ];

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        return [
            'description' => $this->docblock
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->description = $this->required('description');
    }

    /**
     * {@inheritdoc}
     */
    public static function hydrate(array $data = [], Version $version = null): self
    {
        /** @var DescriptionAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setDescription($data['description']);

        return $annotation;
    }

    /**
     * @return string
     */
    public function getDescription(): string
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
