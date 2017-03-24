<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Representation\Types\InvalidTypeException;
use Mill\Exceptions\Resource\Annotations\BadOptionsListException;
use Mill\Exceptions\Resource\Annotations\UnsupportedTypeException;
use Mill\Parser\Annotation;

/**
 * Handler for the `@api-param` annotation.
 *
 */
class ParamAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_VERSIONING = true;
    const SUPPORTS_DEPRECATION = true;

    const REGEX_TYPE = '/^({[^}]*})/';
    const REGEX_OPTIONAL = '/(\(optional\))/';
    const REGEX_VALUES = '/(\[[^\]]*\])/';

    /**
     * Name of this parameter's field.
     *
     * @var string
     */
    protected $field;

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
        'type',
        'values',
        'visible'
    ];

    /**
     * Array of supported parameter types.
     *
     * @var array
     */
    protected $supported_types = [
        'array',
        'boolean',
        'datetime',
        'float',
        'enum',
        'integer',
        'number',
        'object',
        'string',
        'timestamp'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     * @throws UnsupportedTypeException If an unsupported parameter type has been supplied.
     * @throws BadOptionsListException If values are not in the right format.
     */
    protected function parser()
    {
        $parsed = [];
        $doc = trim($this->docblock);

        // Swap in shortcode tokens (if present).
        $tokens = Container::getConfig()->getParameterTokens();
        if (!empty($tokens)) {
            $doc = str_replace(array_keys($tokens), array_values($tokens), $doc);
        }

        // Parameter type is surrounded by `{curly braces}`.
        if (preg_match(self::REGEX_TYPE, $doc, $matches)) {
            $parsed['type'] = substr($matches[1], 1, -1);

            // Verify that the supplied type is supported.
            if (!in_array(strtolower($parsed['type']), $this->supported_types)) {
                throw UnsupportedTypeException::create($doc, $this->controller, $this->method);
            }

            $doc = trim(preg_replace(self::REGEX_TYPE, '', $doc));
        }

        // Parameter capability is surrounded by `+plusses+`.
        if (preg_match(self::REGEX_CAPABILITY, $doc, $matches)) {
            $capability = substr($matches[1], 1, -1);
            $parsed['capability'] = new CapabilityAnnotation($capability, $this->controller, $this->method);

            $doc = trim(preg_replace(self::REGEX_CAPABILITY, '', $doc));
        }

        // Optional flag is marked with `(optional)` parens.
        if (preg_match(self::REGEX_OPTIONAL, $doc, $matches)) {
            $parsed['required'] = false;
            $doc = trim(preg_replace(self::REGEX_OPTIONAL, '', $doc));
        } else {
            $parsed['required'] = true;
        }

        // Parameter values are provided `[in|braces]`.
        if (preg_match(self::REGEX_VALUES, $doc, $matches)) {
            $parsed['values'] = $this->parseEnumValues('param', substr($matches[1], 1, -1));
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
        $this->field = $this->required('field');
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
