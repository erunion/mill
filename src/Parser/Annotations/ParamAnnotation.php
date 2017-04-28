<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Resource\Annotations\UnsupportedTypeException;
use Mill\Parser\Annotation;
use Mill\Parser\MSON;

/**
 * Handler for the `@api-param` annotation.
 *
 */
class ParamAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_VERSIONING = true;
    const SUPPORTS_DEPRECATION = true;
    const SUPPORTS_MSON = true;

    /**
     * Name of this parameter's field.
     *
     * @var string
     */
    protected $field;

    /**
     * Sample data that this parameter might accept.
     *
     * @var string
     */
    protected $sample_data;

    /**
     * Type of data that this parameter supports.
     *
     * @var string
     */
    protected $type;

    /**
     * Flag designating if this parameter is required or not.
     *
     * @var bool
     */
    protected $required = false;

    /**
     * Array of acceptable values for this parameter.
     *
     * @var array|null
     */
    protected $values = [];

    /**
     * Description of what this parameter does.
     *
     * @var string
     */
    protected $description;

    /**
     * Array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'capability',
        'description',
        'field',
        'required',
        'sample_data',
        'type',
        'values',
        'visible'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     * @throws UnsupportedTypeException If an unsupported parameter type has been supplied.
     */
    protected function parser()
    {
        $content = trim($this->docblock);

        // Swap in shortcode tokens (if present).
        $tokens = Container::getConfig()->getParameterTokens();
        if (!empty($tokens)) {
            $content = str_replace(array_keys($tokens), array_values($tokens), $content);
        }

        $mson = (new MSON($this->controller, $this->method))->parse($content);
        $parsed = [
            'field' => $mson->getField(),
            'sample_data' => $mson->getSampleData(),
            'type' => $mson->getType(),
            'required' => $mson->isRequired(),
            'capability' => $mson->getCapability(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ];

        // Create a capability annotation if one was supplied.
        if (!empty($parsed['capability'])) {
            $parsed['capability'] = new CapabilityAnnotation(
                $parsed['capability'],
                $this->controller,
                $this->method
            );
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
        $this->field = $this->required('field');
        $this->sample_data = $this->optional('sample_data'); // @todo make this required
        $this->type = $this->required('type');
        $this->description = $this->required('description');
        $this->required = $this->boolean('required');

        $this->values = $this->optional('values');
        $this->capability = $this->optional('capability');
    }

    /**
     * Get the field that this parameter represents.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Get the sample data that this parameter might accept.
     *
     * @return string
     */
    public function getSampleData()
    {
        return $this->sample_data;
    }

    /**
     * Get the type of variable that this parameter is.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the description for this parameter.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Is this parameter required?
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Get the enumerated values that are allowed on this parameter.
     *
     * @return array|null
     */
    public function getValues()
    {
        return $this->values;
    }
}
