<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;

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
