<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Parser\Annotation;
use Mill\Parser\MSON;
use Mill\Parser\Version;

/**
 * Handler for the `@api-param` annotation.
 *
 */
class ParamAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_DEPRECATION = true;
    const SUPPORTS_MSON = true;
    const SUPPORTS_VERSIONING = true;

    /**
     * Name of this parameter's field.
     *
     * @var string
     */
    protected $field;

    /**
     * Sample data that this parameter might accept.
     *
     * @var false|string
     */
    protected $sample_data = false;

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
     * Flag designating if this parameter is nullable.
     *
     * @var bool
     */
    protected $nullable = false;

    /**
     * Description of what this parameter does.
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
        'capability',
        'description',
        'field',
        'nullable',
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
    protected function parser(): array
    {
        $content = trim($this->docblock);

        // Swap in shortcode tokens (if present).
        $tokens = Container::getConfig()->getParameterTokens();
        if (!empty($tokens)) {
            $content = str_replace(array_keys($tokens), array_values($tokens), $content);
        }

        /** @var string $method */
        $method = $this->method;
        $mson = (new MSON($this->class, $method))->parse($content);
        $parsed = [
            'field' => $mson->getField(),
            'sample_data' => $mson->getSampleData(),
            'type' => $mson->getType(),
            'required' => $mson->isRequired(),
            'nullable' => $mson->isNullable(),
            'capability' => $mson->getCapability(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ];

        // Create a capability annotation if one was supplied.
        if (!empty($parsed['capability'])) {
            $parsed['capability'] = (new CapabilityAnnotation(
                $parsed['capability'],
                $this->class,
                $this->method
            ))->process();
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
    protected function interpreter(): void
    {
        $this->field = $this->required('field');
        $this->sample_data = $this->optional('sample_data');
        $this->type = $this->required('type');
        $this->description = $this->required('description');
        $this->required = $this->boolean('required');

        $this->values = $this->optional('values');
        $this->capability = $this->optional('capability');
        $this->nullable = $this->optional('nullable');
    }

    /**
     * With an array of data that was output from an Annotation, via `toArray()`, hydrate a new Annotation object.
     *
     * @param array $data
     * @param null|Version $version
     * @return self
     */
    public static function hydrate(array $data = [], Version $version = null): self
    {
        /** @var ParamAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setDescription($data['description']);
        $annotation->setField($data['field']);
        $annotation->setNullable($data['nullable']);
        $annotation->setRequired($data['required']);
        $annotation->setSampleData($data['sample_data']);
        $annotation->setType($data['type']);
        $annotation->setValues($data['values']);

        return $annotation;
    }

    /**
     * Get the field that this parameter represents.
     *
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
     * Get the sample data that this parameter might accept.
     *
     * @return false|string
     */
    public function getSampleData()
    {
        return $this->sample_data;
    }

    /**
     * @param false|string $sample_data
     * @return self
     */
    public function setSampleData($sample_data): self
    {
        $this->sample_data = $sample_data;
        return $this;
    }

    /**
     * Get the type of variable that this parameter is.
     *
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
     * Get the description for this parameter.
     *
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
     * Is this parameter required?
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
     * @return self
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    /**
     * Is this parameter nullable?
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @param bool $nullable
     * @return self
     */
    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;
        return $this;
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

    /**
     * @param array|null $values
     * @return self
     */
    public function setValues($values): self
    {
        $this->values = $values;
        return $this;
    }
}
