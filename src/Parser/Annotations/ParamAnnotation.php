<?php
namespace Mill\Parser\Annotations;

use Mill\Application;
use Mill\Container;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Representation\RestrictedFieldNameException;
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
    const SUPPORTS_VENDOR_TAGS = true;
    const SUPPORTS_VERSIONING = true;

    const ARRAYABLE = [
        'description',
        'field',
        'nullable',
        'required',
        'sample_data',
        'subtype',
        'type',
        'values',
        'visible'
    ];

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
     * Subtype of the type of data that this represents.
     *
     * @var false|string
     */
    protected $subtype = false;

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
     * {@inheritdoc}
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
            'subtype' => $mson->getSubtype(),
            'required' => $mson->isRequired(),
            'nullable' => $mson->isNullable(),
            'vendor_tags' => $mson->getVendorTags(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ];

        if (!empty($parsed['field'])) {
            if (strtoupper($parsed['field']) === Application::DOT_NOTATION_ANNOTATION_DATA_KEY) {
                throw RestrictedFieldNameException::create($this->class, $this->method);
            }
        }

        if (!empty($parsed['vendor_tags'])) {
            $parsed['vendor_tags'] = array_map(
                /** @return Annotation */
                function (string $tag) use ($method) {
                    return (new VendorTagAnnotation(
                        $tag,
                        $this->class,
                        $method
                    ))->process();
                },
                $parsed['vendor_tags']
            );
        }

        return $parsed;
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->field = $this->required('field');
        $this->sample_data = $this->optional('sample_data');
        $this->type = $this->required('type');
        $this->subtype = $this->optional('subtype');
        $this->description = $this->required('description');
        $this->required = $this->boolean('required');

        $this->values = $this->optional('values');
        $this->vendor_tags = $this->optional('vendor_tags');
        $this->nullable = $this->optional('nullable');
    }

    /**
     * {@inheritdoc}
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
        $annotation->setSubtype($data['subtype']);
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
     * @return self
     */
    public function setField(string $field): self
    {
        $this->field = $field;
        return $this;
    }

    /**
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
     * @return false|string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @param false|string $subtype
     * @return self
     */
    public function setSubtype($subtype): self
    {
        $this->subtype = $subtype;
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
