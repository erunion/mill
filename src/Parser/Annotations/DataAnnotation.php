<?php
namespace Mill\Parser\Annotations;

use Mill\Application;
use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Parser\Annotation;
use Mill\Parser\MSON;

class DataAnnotation extends Annotation
{
    const SUPPORTS_MSON = true;
    const SUPPORTS_SCOPES = true;
    const SUPPORTS_VENDOR_TAGS = true;
    const SUPPORTS_VERSIONING = true;

    const ARRAYABLE = [
        'description',
        'identifier',
        'nullable',
        'sample_data',
        'subtype',
        'type',
        'values'
    ];

    /** @var string Identifier for this data. */
    protected $identifier;

    /** @var false|string Sample data that this might represent. */
    protected $sample_data = false;

    /** @var string Type of data that this represents. */
    protected $type;

    /** @var false|string Subtype of the type of data that this represents. */
    protected $subtype = false;

    /** @var bool Flag designating if this data is nullable. */
    protected $nullable = false;

    /** @var array|false|null Array of acceptable values for this data. */
    protected $values = [];

    /** @var string Description of what this data represents. */
    protected $description;

    /**
     * {@inheritdoc}
     * @throws RestrictedFieldNameException
     * @throws \Mill\Exceptions\Annotations\UnknownErrorRepresentationException
     * @throws \Mill\Exceptions\Annotations\UnsupportedTypeException
     * @throws \Mill\Exceptions\MSON\ImproperlyWrittenEnumException
     * @throws \Mill\Exceptions\MSON\MissingOptionsException
     */
    protected function parser(): array
    {
        $content = trim($this->docblock);

        /** @var string $method */
        $method = $this->method;

        $mson = (new MSON($this->class, $method))->parse($content);
        $parsed = [
            'identifier' => $mson->getField(),
            'sample_data' => $mson->getSampleData(),
            'type' => $mson->getType(),
            'subtype' => $mson->getSubtype(),
            'nullable' => $mson->isNullable(),
            'vendor_tags' => $mson->getVendorTags(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ];

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

        if (!empty($parsed['identifier'])) {
            if (strtoupper($parsed['identifier']) === Application::DOT_NOTATION_ANNOTATION_DATA_KEY) {
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
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->identifier = $this->required('identifier');
        $this->sample_data = $this->optional('sample_data', true);
        $this->type = $this->required('type');
        $this->subtype = $this->optional('subtype');
        $this->description = $this->required('description');

        $this->values = $this->optional('values');
        $this->vendor_tags = $this->optional('vendor_tags');
        $this->nullable = $this->optional('nullable');
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
     * @return DataAnnotation
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return DataAnnotation
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Set a dot notation prefix on the identifier.
     *
     * @param string $prefix
     * @return DataAnnotation
     */
    public function setIdentifierPrefix(string $prefix): self
    {
        $this->identifier = $prefix . '.' . $this->identifier;
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
     * @return DataAnnotation
     */
    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;
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
     * @return DataAnnotation
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
     * @return DataAnnotation
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
     * @return DataAnnotation
     */
    public function setSubtype($subtype): self
    {
        $this->subtype = $subtype;
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
     * @return DataAnnotation
     */
    public function setValues($values): self
    {
        $this->values = $values;
        return $this;
    }
}
