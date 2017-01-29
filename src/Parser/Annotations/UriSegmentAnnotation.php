<?php
namespace Mill\Parser\Annotations;

use Mill\Exceptions\Resource\Annotations\BadOptionsListException;

/**
 * Handler for the `@api-uriSegment` annotation.
 *
 */
class UriSegmentAnnotation extends ParamAnnotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_VERSIONING = false;
    const SUPPORTS_DEPRECATION = false;

    const REGEX_URI = '/^({[^}]*})/';

    /**
     * URI that this segment is for.
     *
     * @var string
     */
    protected $uri;

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
     * @throws BadOptionsListException If values are not in the right format.
     */
    protected function parser()
    {
        $parsed = [];
        $doc = trim($this->docblock);

        // URI is surrounded by `{curly braces}`.
        if (preg_match(self::REGEX_URI, $doc, $matches)) {
            $parsed['uri'] = substr($matches[1], 1, -1);
            $doc = trim(preg_replace(self::REGEX_URI, '', $doc));
        }

        // Parameter type is surrounded by `{curly braces}`.
        if (preg_match(self::REGEX_TYPE, $doc, $matches)) {
            $parsed['type'] = substr($matches[1], 1, -1);
            $doc = trim(preg_replace(self::REGEX_TYPE, '', $doc));
        }

        // Parameter values are provided `[in|braces]`.
        if (preg_match(self::REGEX_VALUES, $doc, $matches)) {
            $parsed['values'] = explode('|', substr($matches[1], 1, -1));
            if (!empty($parsed['values'])) {
                foreach ($parsed['values'] as $value) {
                    if (strpos($value, ',') !== false) {
                        throw BadOptionsListException::create(
                            $this->docblock,
                            $parsed['values'],
                            $this->controller,
                            $this->method
                        );
                    }
                }
            }

            $doc = trim(preg_replace(self::REGEX_VALUES, '', $doc));
        }

        $parts = explode(' ', $doc);

        // Field and description will be the last two parts, field space description
        $parsed['field'] = array_shift($parts);
        $parsed['description'] = trim(implode(' ', $parts));

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
        $this->uri = $this->required('uri');

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
