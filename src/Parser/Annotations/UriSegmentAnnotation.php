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
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_DEPRECATION = false;
    const SUPPORTS_MSON = true;
    const SUPPORTS_VERSIONING = false;

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
     * @var array|null|false
     */
    protected $values = [];

    /**
     * Return an array of items that should be included in an array representation of this annotation.
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
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     */
    protected function parser()
    {
        $parsed = [];
        $content = trim($this->docblock);

        // URI is surrounded by `{curly braces}`.
        if (preg_match(self::REGEX_URI, $content, $matches)) {
            $parsed['uri'] = substr($matches[1], 1, -1);
            $content = trim(preg_replace(self::REGEX_URI, '', $content));
        }

        $mson = (new MSON($this->class, $this->method))->parse($content);
        $parsed = array_merge($parsed, [
            'field' => $mson->getField(),
            'type' => $mson->getType(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ]);

        return $parsed;
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
        $this->uri = $this->required('uri', false);

        $this->field = $this->required('field');
        $this->type = $this->required('type');
        $this->description = $this->required('description');

        $this->values = $this->optional('values');
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
     * Get the URI that this annotation is on.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set the URI that this annotation is on.
     *
     * @param string $uri
     * @return self
     */
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get the field that this URI segment represents.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set the field that this URI segment represents.
     *
     * @param string $field
     * @return self
     */
    public function setField(string $field): self
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Get the type of field that this URI segment represents.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type of field that this URI segment represents.
     *
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the description for this URI segment.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description for this URI segment.
     *
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the enumerated values that are allowed on this URI segment.
     *
     * @return array|null|false
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Set the enumerated values that are allowed on this URI segment.
     *
     * @param array|null|false $values
     * @return self
     */
    public function setValues($values): self
    {
        $this->values = $values;
        return $this;
    }
}
