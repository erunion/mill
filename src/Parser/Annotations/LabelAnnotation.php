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
    /** @var string */
    protected $label;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'label'
    ];

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        return [
            'label' => $this->content
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->label = $this->required('label');
    }

    /**
     * {@inheritdoc}
     */
    /*public static function hydrate(array $data = [], Version $version = null): self
    {
        // @var LabelAnnotation $annotation
        $annotation = parent::hydrate($data, $version);
        $annotation->setLabel($data['label']);

        return $annotation;
    }*/

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return self
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }
}
