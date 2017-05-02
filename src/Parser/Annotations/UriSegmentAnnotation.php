<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\MSON;

/**
 * Handler for the `@api-uriSegment` annotation.
 *
 */
class UriSegmentAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_VERSIONING = false;
    const SUPPORTS_DEPRECATION = false;
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
     * @var array|null
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
     * Get the URI that this annotation is on.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
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
     * Get the type of field that this URI segment represents.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
}
