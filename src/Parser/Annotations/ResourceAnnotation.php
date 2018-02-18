<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-resource` annotation.
 *
 */
class ResourceAnnotation extends Annotation
{
    /** @var string */
    protected $name;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'name'
    ];

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        return [
            'name' => $this->content
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->name = $this->required('name');
    }

    /**
     * {@inheritdoc}
     */
    /*public static function hydrate(array $data = [], Version $version = null): self
    {
        // @var ResourceAnnotation $annotation
        $annotation = parent::hydrate($data, $version);
        $annotation->setName($data['name']);

        return $annotation;
    }*/

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
