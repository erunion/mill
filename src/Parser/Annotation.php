<?php
namespace Mill\Parser;

use Mill\Exceptions\Annotations\BadOptionsListException;
use Mill\Exceptions\Annotations\InvalidMSONSyntaxException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;

/**
 * Base class for supported annotations.
 *
 */
abstract class Annotation
{
    const REGEX_CAPABILITY = '/(\+[^\+]*\+)/';

    /**
     * The raw annotation from the docblock.
     *
     * @var string
     */
    protected $docblock;

    /**
     * Class that this annotation is within.
     *
     * @var string
     */
    protected $class;

    /**
     * Class method that this annotation is within.
     *
     * @var mixed
     */
    protected $method;

    /**
     * Capability that this annotation requires.
     *
     * @var string|bool
     */
    protected $capability = false;

    /**
     * Flag designating if this annotation is visible or not.
     *
     * @var bool|null
     */
    protected $visible = null;

    /**
     * Version representation that this annotation is supported on.
     *
     * @var \Mill\Parser\Version|false
     */
    protected $version = false;

    /**
     * Flag designating that this annotation is deprecated or not.
     *
     * @var bool
     */
    protected $deprecated = false;

    /**
     * Array of extra data needed to build the annotation.
     *
     * This is used for building representations in the `@api-field` and `@api-type` annotations.
     *
     * @var array
     */
    protected $extra_data = [];

    /**
     * Array of parsed data from this annotation.
     *
     * @var array
     */
    protected $parsed_data = [];

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [];

    /**
     * Does this annotation require a visibility decorator?
     *
     * @var bool|null
     */
    const REQUIRES_VISIBILITY_DECORATOR = null;

    /**
     * Does this annotation support versioning?
     *
     * @return bool|null
     */
    const SUPPORTS_VERSIONING = null;

    /**
     * Does this annotation support being deprecated?
     *
     * @return bool|null
     */
    const SUPPORTS_DEPRECATION = null;

    /**
     * Is this annotation written using MSON?
     *
     * @return bool|null
     */
    const SUPPORTS_MSON = null;

    /**
     * @param string $doc
     * @param string $class
     * @param string|null $method
     * @param \Mill\Parser\Version|null $version
     * @param array $extra_data
     */
    public function __construct($doc, $class, $method, Version $version = null, $extra_data = [])
    {
        $this->docblock = $doc;

        $this->class = $class;
        $this->method = $method;

        // Since you can't set falsy defaults in methods, and we don't want a `null` version, let's force a false
        // default if no version was passed in.
        $this->version = (!empty($version)) ? $version : false;
        $this->extra_data = $extra_data;

        $this->parsed_data = $this->parser();

        $this->interpreter();
    }

    /**
     * Extract a required field from the parsed dataset.
     *
     * @param string $field
     * @param bool $is_mson_field
     * @return mixed
     * @throws InvalidMSONSyntaxException If the annotation contains invalid MSON.
     * @throws MissingRequiredFieldException If the supplied field is missing in the parsed dataset.
     */
    protected function required($field, $is_mson_field = true)
    {
        if (empty($this->parsed_data[$field])) {
            // If this field was written in MSON, but isn't present, and this annotation supports MSON, let's return an
            // invalid MSON exception because that means that we just weren't able to parse the MSON that they supplied.
            if ($is_mson_field && static::SUPPORTS_MSON) {
                throw InvalidMSONSyntaxException::create(
                    $field,
                    $this->getAnnotationName(),
                    $this->docblock,
                    $this->class,
                    $this->method
                );
            }

            throw MissingRequiredFieldException::create(
                $field,
                $this->getAnnotationName(),
                $this->docblock,
                $this->class,
                $this->method
            );
        }

        return $this->parsed_data[$field];
    }

    /**
     * Extract an optional field from the parsed dataset.
     *
     * @param string $field
     * @return mixed|null
     */
    protected function optional($field)
    {
        if (empty($this->parsed_data[$field])) {
            return false;
        }

        return $this->parsed_data[$field];
    }

    /**
     * Extract a boolean field from the parsed dataset.
     *
     * @param string $field
     * @return bool
     */
    protected function boolean($field)
    {
        return !empty($this->parsed_data[$field]);
    }

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations
     * representation looks like.
     *
     * @return array
     */
    abstract protected function parser();

    /**
     * Interpret the parsed annotation data and set local variables to build the annotation.
     *
     * To facilitate better error messaging, the order in which items are interpreted here should be match the schema
     * of the annotation.
     *
     * @return void
     */
    abstract protected function interpreter();

    /**
     * Does this annotation require a visibility decorator?
     *
     * @return bool
     */
    public function requiresVisibilityDecorator()
    {
        return static::REQUIRES_VISIBILITY_DECORATOR;
    }

    /**
     * Does this annotation support versioning?
     *
     * @return bool
     */
    public function supportsVersioning()
    {
        return static::SUPPORTS_VERSIONING;
    }

    /**
     * Does this annotation support versioning?
     *
     * @return bool
     */
    public function supportsDeprecation()
    {
        return static::SUPPORTS_DEPRECATION;
    }

    /**
     * Given our common format for describing enumerated values, parse it out and run validation.
     *
     * @param string $annotation
     * @param string $values
     * @return array
     * @throws BadOptionsListException If the enum values were not written in the proper format.
     */
    public function parseEnumValues($annotation, $values)
    {
        $values = explode('|', $values);
        if (!empty($values)) {
            foreach ($values as $k => $value) {
                if (strpos($value, ',') !== false) {
                    throw BadOptionsListException::create(
                        $annotation,
                        $this->docblock,
                        $values,
                        $this->class,
                        $this->method
                    );
                }

                // Values might be on multiple lines, or be surrounded with whitespace to make them easier to read,
                // so let's clean them up a bit.
                $values[$k] = trim($value);
            }

            // Keep the array of values alphabetical so it's cleaner when generated into documentation.
            sort($values);
        }

        return $values;
    }

    /**
     * Convert the parsed annotation into an array.
     *
     * @return array
     */
    public function toArray()
    {
        $arr = [];
        foreach ($this->arrayable as $var) {
            if ($var === 'visible') {
                $arr[$var] = $this->isVisible();
            } elseif ($this->{$var} instanceof Annotation) {
                $arr += $this->{$var}->toArray();
            } else {
                $arr[$var] = $this->{$var};
            }
        }

        // If this annotation supports deprecation, then we should include so in the array representation.
        if ($this->supportsDeprecation()) {
            $arr['deprecated'] = $this->isDeprecated();
        }

        // If this annotation supports versioning, then we should include the version in the array representation.
        if ($this->supportsVersioning()) {
            $arr['version'] = false;

            if ($this->version instanceof Version) {
                $arr['version'] = $this->version->getConstraint();
            }
        }

        // Just to keep things nice.
        ksort($arr);

        return $arr;
    }

    /**
     * Pull the annotation name for the current annotation.
     *
     * For example on `ParamAnnotation`, this returns `param`.
     *
     * @return string
     */
    protected function getAnnotationName()
    {
        // Rad snippet for pulling the short name of a class without needing reflection.
        // @link http://stackoverflow.com/a/27457689/105698
        $class = substr(strrchr(get_class($this), '\\'), 1);
        return strtolower(str_replace('Annotation', '', $class));
    }

    /**
     * Get the visibility of the current annotation.
     *
     * @return bool
     */
    public function isVisible()
    {
        return !!$this->visible;
    }

    /**
     * Does this annotation have an explicit visibility defined?
     *
     * @return bool
     */
    public function hasVisibility()
    {
        return !is_null($this->visible);
    }

    /**
     * Set the visibility on the current annotation.
     *
     * @param bool $visibility
     * @return Annotation
     */
    public function setVisibility($visibility)
    {
        $this->visible = $visibility;
        return $this;
    }

    /**
     * Is this annotation deprecated?
     *
     * @return bool
     */
    public function isDeprecated()
    {
        return $this->deprecated;
    }

    /**
     * Set if this annotation is deprecated or not.
     *
     * @param bool $deprecated
     * @return Annotation
     */
    public function setDeprecated($deprecated)
    {
        $this->deprecated = $deprecated;
        return $this;
    }

    /**
     * Return the capability, if any, that has been set.
     *
     * @return string|false|bool
     */
    public function getCapability()
    {
        return $this->capability;
    }

    /**
     * Get the version constraint, if any, that this parameter is part of.
     *
     * @return Version|false
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set a version that this annotation is available on. This is specifically used in tandem with `@api-see`
     * annotations.
     *
     * @param Version $version
     * @return Annotation
     */
    public function setVersion(Version $version)
    {
        $this->version = $version;
        return $this;
    }
}
