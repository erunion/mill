<?php
namespace Mill\Parser;

use Mill\Contracts\Arrayable;
use Mill\Exceptions\Annotations\InvalidMSONSyntaxException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;

abstract class Annotation implements Arrayable
{
    /** @var bool Does this annotation require a visibility decorator? */
    const REQUIRES_VISIBILITY_DECORATOR = false;

    /** @var bool Does this annotation support being deprecated? */
    const SUPPORTS_DEPRECATION = false;

    /** @var bool Is this annotation written using MSON? */
    const SUPPORTS_MSON = false;

    /** @var bool Does this annotation support auth token scopes? */
    const SUPPORTS_SCOPES = false;

    /** @var bool Does this annotation support vendor tags? */
    const SUPPORTS_VENDOR_TAGS = false;

    /** @var bool Does this annotation support versioning? */
    const SUPPORTS_VERSIONING = false;

    /** @var array An array of items that should be included in an array representation of this annotation. */
    const ARRAYABLE = [];

    /** @var string The raw annotation from the docblock. */
    protected $docblock;

    /** @var string Class that this annotation is within. */
    protected $class;

    /** @var null|string Class method that this annotation is within. */
    protected $method = null;

    /** @var array Vendor tags that this annotation possesses. */
    protected $vendor_tags = [];

    /** @var bool|null Flag designating if this annotation is visible or not. */
    protected $visible = null;

    /** @var false|Version Version representation that this annotation is supported on. */
    protected $version = false;

    /** @var bool Flag designating that this annotation is deprecated or not. */
    protected $deprecated = false;

    /** @var array Array of all authentication scopes required for this annotation. */
    protected $scopes = [];

    /** @var array Array of parsed data from this annotation. */
    protected $parsed_data = [];

    /**
     * @param string $doc
     * @param string $class
     * @param null|string $method
     * @param null|Version $version
     */
    public function __construct(string $doc, string $class, string $method = null, Version $version = null)
    {
        $this->docblock = $doc;
        $this->class = $class;
        $this->method = $method;

        // Since you can't set falsy defaults in methods, and we don't want a `null` version, let's force a false
        // default if no version was passed in.
        $this->version = (!empty($version)) ? $version : false;
    }

    /**
     * Process and parse the annotation docblock that was created.
     *
     * @return Annotation
     */
    public function process(): self
    {
        $this->parsed_data = $this->parser();

        $this->interpreter();

        return $this;
    }

    /**
     * Extract a required field from the parsed dataset.
     *
     * @param string $field
     * @param bool $is_mson_field
     * @return string
     * @throws InvalidMSONSyntaxException If the annotation contains invalid MSON.
     * @throws MissingRequiredFieldException If the supplied field is missing in the parsed dataset.
     */
    protected function required(string $field, bool $is_mson_field = true): string
    {
        if (empty($this->parsed_data[$field])) {
            /** @var string $method */
            $method = $this->method;

            // If this field was written in MSON, but isn't present, and this annotation supports MSON, let's return an
            // invalid MSON exception because that means that we just weren't able to parse the MSON that they supplied.
            if ($is_mson_field && static::SUPPORTS_MSON) {
                throw InvalidMSONSyntaxException::create(
                    $field,
                    $this->getAnnotationName(),
                    $this->docblock,
                    $this->class,
                    $method
                );
            }

            throw MissingRequiredFieldException::create(
                $field,
                $this->getAnnotationName(),
                $this->docblock,
                $this->class,
                $method
            );
        }

        return $this->parsed_data[$field];
    }

    /**
     * Extract an optional field from the parsed dataset.
     *
     * @param string $field
     * @param bool $allow_zero
     * @return false|mixed
     */
    protected function optional(string $field, $allow_zero = false)
    {
        if ($allow_zero && $this->parsed_data[$field] === '0') {
            return $this->parsed_data[$field];
        } elseif (empty($this->parsed_data[$field])) {
            if (is_array($this->parsed_data[$field])) {
                return [];
            }

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
    protected function boolean(string $field): bool
    {
        return !empty($this->parsed_data[$field]);
    }

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations
     * representation looks like.
     *
     * @return array
     */
    abstract protected function parser(): array;

    /**
     * Interpret the parsed annotation data and set local variables to build the annotation.
     *
     * To facilitate better error messaging, the order in which items are interpreted here should be match the schema
     * of the annotation.
     *
     * @return void
     */
    abstract protected function interpreter(): void;

    /**
     * Does this annotation require a visibility decorator?
     *
     * @return bool
     */
    public function requiresVisibilityDecorator(): bool
    {
        return static::REQUIRES_VISIBILITY_DECORATOR;
    }

    /**
     * Does this annotation support versioning?
     *
     * @return bool
     */
    public function supportsDeprecation(): bool
    {
        return static::SUPPORTS_DEPRECATION;
    }

    /**
     * Does this annotation support authentication scopes?
     *
     * @return bool
     */
    public function supportsScopes(): bool
    {
        return static::SUPPORTS_SCOPES;
    }

    /**
     * Does this annotation support vendor tags?
     *
     * @return bool
     */
    public function supportsVendorTags(): bool
    {
        return static::SUPPORTS_VENDOR_TAGS;
    }

    /**
     * Does this annotation support versioning?
     *
     * @return bool
     */
    public function supportsVersioning(): bool
    {
        return static::SUPPORTS_VERSIONING;
    }

    /**
     * {{@inheritdoc}}
     */
    public function toArray(): array
    {
        $arr = [];
        foreach (static::ARRAYABLE as $var) {
            if ($this->{$var} instanceof Annotation) {
                $arr += $this->{$var}->toArray();
            } else {
                $arr[$var] = $this->{$var};
            }
        }

        // If this annotation requires visibility decorators, then we should include that.
        if ($this->requiresVisibilityDecorator()) {
            $arr['visible'] = $this->isVisible();
        }

        if ($this->supportsDeprecation()) {
            $arr['deprecated'] = $this->isDeprecated();
        }

        if ($this->supportsScopes()) {
            $arr['scopes'] = [];

            /** @var Annotation $scope */
            foreach ($this->getScopes() as $scope) {
                $arr['scopes'][] = $scope->toArray();
            }
        }

        if ($this->supportsVendorTags()) {
            $arr['vendor_tags'] = [];

            /** @var Annotation $scope */
            foreach ($this->getVendorTags() as $vendor_tag) {
                $arr['vendor_tags'][] = $vendor_tag->getVendorTag();
            }
        }

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
    protected function getAnnotationName(): string
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
    public function isVisible(): bool
    {
        return !!$this->visible;
    }

    /**
     * Does this annotation have an explicit visibility defined?
     *
     * @return bool
     */
    public function hasVisibility(): bool
    {
        return !is_null($this->visible);
    }

    /**
     * Set the visibility on the current annotation.
     *
     * @param bool $visibility
     * @return Annotation
     */
    public function setVisibility(bool $visibility): self
    {
        $this->visible = $visibility;
        return $this;
    }

    /**
     * Is this annotation deprecated?
     *
     * @return bool
     */
    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    /**
     * Set if this annotation is deprecated or not.
     *
     * @param bool $deprecated
     * @return Annotation
     */
    public function setDeprecated(bool $deprecated): self
    {
        $this->deprecated = $deprecated;
        return $this;
    }

    /**
     * Set any required authentication scopes to this annotation.
     *
     * @param array<Annotation> $scopes
     * @return Annotation
     */
    public function setScopes(array $scopes): self
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * Get all required authentication scopes for this annotation.
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Return an array of vendor tags that this annotation possesses.
     * @return array
     */
    public function getVendorTags(): array
    {
        return $this->vendor_tags;
    }

    /**
     * Set vendor tags that this annotation possesses.
     *
     * @param array $vendor_tags
     * @return Annotation
     */
    public function setVendorTags(array $vendor_tags = []): self
    {
        $this->vendor_tags = $vendor_tags;
        return $this;
    }

    /**
     * Get the version constraint, if any, that this parameter is part of.
     *
     * @return false|Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set a version that this annotation is available on. This is specifically used in tandem with representation
     * depth parsing.
     *
     * @param Version $version
     * @return Annotation
     */
    public function setVersion(Version $version): self
    {
        $this->version = $version;
        return $this;
    }
}
