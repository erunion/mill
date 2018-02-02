<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\MSON;
use Mill\Parser\Version;

/**
 * Handler for the `@api-uriSegment` annotation.
 *
 */
class UriSegmentAnnotation extends Annotation
{
    const SUPPORTS_MSON = true;

    const REGEX_URI = '/^({[^}]*})/';

    /**
     * URI that this segment is for.
     *
     * @var string
     */
    protected $uri;

    /**
     * Name of this segment's field.
     *
     * @var string
     */
    protected $field;

    /**
     * Type of data that this segment supports.
     *
     * @var string
     */
    protected $type;

    /**
     * Description of what this segment does.
     *
     * @var string
     */
    protected $description;

    /**
     * Array of acceptable values for this parameter.
     *
     * @var array|false|null
     */
    protected $values = [];

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'description',
        'field',
        'type',
        'uri',
        'values'
    ];

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        $parsed = [];
        $content = trim($this->docblock);

        // URI is surrounded by `{curly braces}`.
        if (preg_match(self::REGEX_URI, $content, $matches)) {
            $parsed['uri'] = substr($matches[1], 1, -1);
            $content = trim(preg_replace(self::REGEX_URI, '', $content));
        }

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
        $this->uri = $this->required('uri', false);

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
        /** @var UriSegmentAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setDescription($data['description']);
        $annotation->setField($data['field']);
        $annotation->setType($data['type']);
        $annotation->setUri($data['uri']);
        $annotation->setValues($data['values']);

        return $annotation;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     * @return self
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
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
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setValues($values): self
    {
        $this->values = $values;
        return $this;
    }
}
