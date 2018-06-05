<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\Version;

class DescriptionAnnotation extends Annotation
{
    const ARRAYABLE = [
        'description'
    ];

    /** @var string */
    protected $description;

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
     * @return DescriptionAnnotation
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
