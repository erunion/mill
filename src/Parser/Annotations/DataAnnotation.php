<?php
namespace Mill\Parser\Annotations;

use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Parser\Annotation;
use Mill\Parser\MSON;
use Mill\Parser\Representation\Documentation;

/**
 * Handler for the `@api-data` annotation.
 *
 */
class DataAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_VERSIONING = true;
    const SUPPORTS_DEPRECATION = false;
    const SUPPORTS_MSON = true;

    /**
     * Identifier for this data.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Sample data that this might represent.
     *
     * @var string
     */
    protected $sample_data;

    /**
     * Type of data that this represents.
     *
     * @var string
     */
    protected $type;

    /**
     * Subtype of the type of data that this represents.
     *
     * @var string|false
     */
    protected $subtype = false;

    /**
     * Array of acceptable values for this data.
     *
     * @var array|null
     */
    protected $values = [];

    /**
     * Description of what this data represents.
     *
     * @var string
     */
    protected $description;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'capability',
        'description',
        'identifier',
        'sample_data',
        'subtype',
        'type',
        'values',
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     * @throws RestrictedFieldNameException If a restricted `@api-field` name is detected.
     */
    protected function parser()
    {
        $content = trim($this->docblock);

        $mson = (new MSON($this->class, $this->method))->parse($content);
        $parsed = [
            'identifier' => $mson->getField(),
            'sample_data' => $mson->getSampleData(),
            'type' => $mson->getType(),
            'subtype' => $mson->getSubtype(),
            'capability' => $mson->getCapability(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ];

        // Create a capability annotation if one was supplied.
        if (!empty($parsed['capability'])) {
            $parsed['capability'] = new CapabilityAnnotation(
                $parsed['capability'],
                $this->class,
                $this->method
            );
        }

        if (!empty($parsed['identifier'])) {
            if (strtoupper($parsed['identifier']) === Documentation::DOT_NOTATION_ANNOTATION_DATA_KEY) {
                throw RestrictedFieldNameException::create($this->class, $this->method);
            }
        }

        // If we have values present, but no sample data, set the sample as the first item in the values list.
        if (!empty($parsed['values']) && empty($parsed['sample_data'])) {
            $parsed['sample_data'] = array_keys($parsed['values'])[0];
        }

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
        $this->identifier = $this->required('identifier');
        $this->sample_data = $this->optional('sample_data');
        $this->type = $this->required('type');
        $this->subtype = $this->optional('subtype');
        $this->description = $this->required('description');

        $this->values = $this->optional('values');
        $this->capability = $this->optional('capability');
    }

    /**
     * Get the field name.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set a dot notation prefix on the identifier.
     *
     * @param string $prefix
     * @return DataAnnotation
     */
    public function setIdentifierPrefix($prefix)
    {
        $this->identifier = $prefix . '.' . $this->identifier;
        return $this;
    }
}
