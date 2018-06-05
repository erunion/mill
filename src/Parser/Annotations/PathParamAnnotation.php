<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\MSON;
use Mill\Parser\Version;

class PathParamAnnotation extends Annotation
{
    const SUPPORTS_MSON = true;

    const ARRAYABLE = [
        'description',
        'field',
        'type',
        'values'
    ];

    /** @var string Name of this param's field. */
    protected $field;

    /** @var string Type of data that this param supports. */
    protected $type;

    /** @var string Description of what this param does. */
    protected $description;

    /** @var array|false|null Array of acceptable values for this parameter. */
    protected $values = [];

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        $parsed = [];
        $content = trim($this->docblock);

        /** @var string $method */
        $method = $this->method;
        $mson = (new MSON($this->class, $method))->parse($content);
        $parsed = array_merge($parsed, [
            'field' => $mson->getField(),
            'type' => $mson->getType(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ]);

        return $parsed;
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->field = $this->required('field');
        $this->type = $this->required('type');
        $this->description = $this->required('description');

        $this->values = $this->optional('values');
    }

    /**
     * {@inheritdoc}
     */
    public static function hydrate(array $data = [], Version $version = null)
    {
        /** @var PathParamAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setDescription($data['description']);
        $annotation->setField($data['field']);
        $annotation->setType($data['type']);
        $annotation->setValues($data['values']);

        return $annotation;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     * @return PathParamAnnotation
     */
    public function setField(string $field): self
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return PathParamAnnotation
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
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
     * @return PathParamAnnotation
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array|false|null
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array|false|null $values
     * @return PathParamAnnotation
     */
    public function setValues($values): self
    {
        $this->values = $values;
        return $this;
    }
}
