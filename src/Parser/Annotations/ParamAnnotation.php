<?php
namespace Mill\Parser\Annotations;

use Mill\Application;
use Mill\Container;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Parser\Annotation;
use Mill\Parser\MSON;
use Mill\Parser\Version;

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

    /** @var string Name of this parameter's field. */
    protected $field;

    /** @var false|string Sample data that this parameter might accept. */
    protected $sample_data = false;

    /** @var string Type of data that this parameter supports. */
    protected $type;

    /** @var false|string Subtype of the type of data that this represents. */
    protected $subtype = false;

    /** @var bool Flag designating if this parameter is required or not. */
    protected $required = false;

    /** @var bool Flag designating if this parameter is nullable. */
    protected $nullable = false;

    /** @var string Description of what this parameter does. */
    protected $description;

    /** @var array|null Array of acceptable values for this parameter. */
    protected $values = [];

    /**
     * {@inheritdoc}
     * @throws RestrictedFieldNameException
     * @throws UnsupportedTypeException
     * @throws \Mill\Exceptions\Annotations\UnknownErrorRepresentationException
     * @throws \Mill\Exceptions\MSON\ImproperlyWrittenEnumException
     * @throws \Mill\Exceptions\MSON\MissingOptionsException
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
     * @return string
     */
    public function getPayloadFormat(): string
    {
        return 'body';
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
     * @return ParamAnnotation
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
     * @return ParamAnnotation
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
     * @return ParamAnnotation
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
     * @return ParamAnnotation
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
     * @return ParamAnnotation
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
     * @return ParamAnnotation
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
     * @return ParamAnnotation
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
     * @return ParamAnnotation
     */
    public function setValues($values): self
    {
        $this->values = $values;
        return $this;
    }
}
