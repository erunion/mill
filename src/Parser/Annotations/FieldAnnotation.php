<?php
namespace Mill\Parser\Annotations;

use Mill\Exceptions\Representation\DuplicateAnnotationsOnFieldException;
use Mill\Exceptions\Representation\MissingFieldAnnotationException;
use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Exceptions\Representation\Types\InvalidTypeException;
use Mill\Exceptions\Representation\Types\MissingOptionsException;
use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-field` annotation.
 *
 */
class FieldAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_VERSIONING = true;
    const SUPPORTS_DEPRECATION = false;

    const OPTIONS_PATTERN = '~\[(.+)\]~';

    /**
     * Field label.
     *
     * @var string|array
     */
    protected $label = [];

    /**
     * Name of this parameter's field.
     *
     * @var string
     */
    protected $field;

    /**
     * Capability that this annotation requires.
     *
     * @var TypeAnnotation
     */
    protected $type;

    /**
     * Description for what the content of `@api-type` is. Used in `representation` types.
     *
     * @var string|null
     */
    protected $subtype = null;

    /**
     * Array of available options.
     *
     * @var array|null
     */
    protected $options = null;

    /**
     * Capability that this annotation requires.
     *
     * @var CapabilityAnnotation|null
     */
    protected $capability = null;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'capability',
        'field',
        'label',
        'options',
        'type'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     * @throws DuplicateAnnotationsOnFieldException If duplicate annotations are found on the same representation field.
     * @throws MissingFieldAnnotationException If a required `@api-field` annotation is missing.
     * @throws MissingFieldAnnotationException If a required `@api-type` annotation is missing.
     * @throws RestrictedFieldNameException If a restricted `@api-field` name is detected.
     * @throws InvalidTypeException If a supplied `@api-type` annotation is not a type that we recognize.
     * @throws MissingOptionsException If a supplied `@api-type` annotation is missing a corresponding `@api-options`.
     */
    protected function parser()
    {
        // To save us from duplicating a bunch of DuplicateAnnotationsOnFieldException exceptions, pre-parse the
        // docblock lines into an array of annotations that should be parsed out.
        $annotations = [];
        foreach ($this->extra_data['docblock_lines'] as $line) {
            list($_, $annotation, $decorator, $_last_decorator, $data) = $line;

            if (isset($annotations[$annotation])) {
                throw DuplicateAnnotationsOnFieldException::create($annotation, $this->controller, $this->method);
            }

            $annotations[$annotation] = [
                'decorator' => $decorator,
                'data' => trim($data)
            ];
        }

        if (!isset($annotations['field']) || empty($annotations['field']['data'])) {
            throw MissingFieldAnnotationException::create('field', $this->controller, $this->method);
        } elseif (!isset($annotations['type']) || empty($annotations['type']['data'])) {
            throw MissingFieldAnnotationException::create('type', $this->controller, $this->method);
        }

        $type_object = false;
        $type = '';
        $subtype = false;
        $parsed = [];
        foreach ($annotations as $name => $details) {
            $data = $details['data'];

            switch ($name) {
                // `@api-capability`
                case 'capability':
                    $parsed['capability'] = new CapabilityAnnotation($data, $this->controller, $this->method);
                    break;

                // `@api-field`
                case 'field':
                    if (strtoupper($data) === '__FIELD_DATA__') {
                        throw RestrictedFieldNameException::create($this->controller, $this->method);
                    }

                    $parsed['field'] = $data;
                    break;

                // `@api-label`
                case 'label':
                    $parsed['label'] = new LabelAnnotation($data, $this->controller, $this->method);
                    break;

                // `@api-options`
                case 'options':
                    if (preg_match(self::OPTIONS_PATTERN, $data, $options_matches)) {
                        $parsed['options'] = array_filter(explode('|', $options_matches[1]));

                        // Keep the array of options alphabetical so it's cleaner when generated into documentation.
                        sort($parsed['options']);
                    }
                    break;

                // `@api-subtype`
                case 'subtype':
                    $subtype = $data;
                    break;

                // `@api-type`
                case 'type':
                    $class = '\Mill\Parser\Representation\Types\\' . ucfirst(strtolower($data)) . 'Type';
                    if (!class_exists($class)) {
                        throw InvalidTypeException::create($data, $this->controller, $this->method);
                    }

                    /** @var \Mill\Parser\Representation\Type $type_object */
                    $type_object = new $class;
                    $type = $data;
                    break;

                // `@api-version`
                case 'version':
                    $parsed['version'] = new Version($data, $this->controller, $this->method);
                    break;
            }
        }

        $parsed['type'] = new TypeAnnotation(
            $type,
            $this->controller,
            $this->method,
            null,
            [
                'object' => $type_object,
                'subtype' => $subtype
            ]
        );

        // If this `@api-type` requires `@api-options`, but none were found, throw an error.
        if ($parsed['type']->getObject()->requiresOptions() && empty($parsed['options'])) {
            throw MissingOptionsException::create($type, $this->controller, $this->method);
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
        $this->label = $this->required('label');
        $this->field = $this->required('field');

        $this->capability = $this->optional('capability');

        $this->type = $this->required('type');
        $this->subtype = $this->optional('subtype');

        $this->options = $this->optional('options');

        $this->version = $this->optional('version');
    }

    /**
     * Get the field name.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->field;
    }

    /**
     * Set a dot notation prefix on the field name.
     *
     * @param string $prefix
     * @return FieldAnnotation
     */
    public function setFieldNamePrefix($prefix)
    {
        $this->field = $prefix . '.' . $this->field;
        return $this;
    }
}
