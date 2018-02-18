<?php
namespace Mill\Parser\Annotations;

use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Parser\Annotation;
use Mill\Parser\MSON;
use Mill\Parser\Representation\Documentation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-data` annotation.
 *
 */
class DataAnnotation extends Annotation
{
    const SUPPORTS_MSON = true;
    const SUPPORTS_SCOPES = true;
    const SUPPORTS_VERSIONING = true;

    /**
     * Identifier for this data.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Sample data that this might represent.
     *
     * @var false|string
     */
    protected $sample_data = false;

    /**
     * Type of data that this represents.
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
     * Flag designating if this data is nullable.
     *
     * @var bool
     */
    protected $nullable = false;

    /**
     * Array of acceptable values for this data.
     *
     * @var array|false|null
     */
    protected $values = [];

    /**
     * Description of what this data represents.
     *
     * @var string
     */
    protected $description;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'capability',
        'description',
        'identifier',
        'nullable',
        'sample_data',
        'subtype',
        'type',
        'values'
    ];

    /**
     * {@inheritdoc}
     * @throws RestrictedFieldNameException If a restricted `@api-field` name is detected.
     */
    protected function parser(): array
    {
        $mson = (new MSON($this->application, $this->docblock))->parse($this->content);
        $parsed = [
            'identifier' => $mson->getField(),
            'sample_data' => $mson->getSampleData(),
            'type' => $mson->getType(),
            'subtype' => $mson->getSubtype(),
            'nullable' => $mson->isNullable(),
            'capability' => $mson->getCapability(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ];

        // Create a capability annotation if one was supplied.
        if (!empty($parsed['capability'])) {
            $parsed['capability'] = (new CapabilityAnnotation(
                $this->application,
                $parsed['capability'],
                $this->docblock
            ))->process();
        }

        if (!empty($parsed['identifier'])) {
            if (strtoupper($parsed['identifier']) === Documentation::DOT_NOTATION_ANNOTATION_DATA_KEY) {
                $this->application->trigger(RestrictedFieldNameException::create($this->docblock));
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
        $this->capability = $this->optional('capability');
        $this->nullable = $this->optional('nullable');
    }

    /**
     * {@inheritdoc}
     */
    /*public static function hydrate(array $data = [], Version $version = null): self
    {
        // @var DataAnnotation $annotation
        $annotation = parent::hydrate($data, $version);
        $annotation->setDescription($data['description']);
        $annotation->setIdentifier($data['identifier']);
        $annotation->setNullable($data['nullable']);
        $annotation->setSampleData($data['sample_data']);
        $annotation->setSubtype($data['subtype']);
        $annotation->setType($data['type']);
        $annotation->setValues($data['values']);

        return $annotation;
    }*/

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
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return self
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
     * @return self
     */
    public function setIdentifierPrefix($prefix): self
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
     * @return self
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
     * @return self
     */
    public function setSampleData($sample_data): self
    {
        $this->sample_data = $sample_data;
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
     * @return array|false|null
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array|false|null $values
     * @return self
     */
    public function setValues($values): self
    {
        $this->values = $values;
        return $this;
    }
}
