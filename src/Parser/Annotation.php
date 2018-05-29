<?php
namespace Mill\Parser;

use Mill\Exceptions\Annotations\InvalidMSONSyntaxException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\PathAnnotation;
use Mill\Parser\Annotations\ScopeAnnotation;
use Mill\Parser\Annotations\VendorTagAnnotation;

/**
 * Base class for supported annotations.
 *
 */
abstract class Annotation
{
    /**
     * Does this annotation require a visibility decorator?
     *
     * @var bool
     */
    const REQUIRES_VISIBILITY_DECORATOR = false;

    /**
     * Does this annotation support being deprecated?
     *
     * @var bool
     */
    const SUPPORTS_DEPRECATION = false;

    /**
     * Is this annotation written using MSON?
     *
     * @var bool
     */
    const SUPPORTS_MSON = false;

    /**
     * Does this annotation support auth token scopes?
     *
     * @var bool
     */
    const SUPPORTS_SCOPES = false;

    /**
     * Does this annotation support vendor tags?
     *
     * @var bool
     */
    const SUPPORTS_VENDOR_TAGS = false;

    /**
     * Does this annotation support versioning?
     *
     * @var bool
     */
    const SUPPORTS_VERSIONING = false;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    const ARRAYABLE = [];

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
     * @var null|string
     */
    protected $method = null;

    /**
     * Vendor tags that this annotation possesses.
     *
     * @var array
     */
    protected $vendor_tags = [];

    /**
     * Flag designating if this annotation is visible or not.
     *
     * @var bool|null
     */
    protected $visible = null;

    /**
     * Version representation that this annotation is supported on.
     *
     * @var false|Version
     */
    protected $version = false;

    /**
     * Flag designating that this annotation is deprecated or not.
     *
     * @var bool
     */
    protected $deprecated = false;

    /**
     * Array of all authentication scopes required for this annotation.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Array of all available aliases for this annotation.
     *
     * @var array<Annotation>
     */
    protected $aliases = [];

    /**
     * Flag designating that this annotation is aliased or not.
     *
     * @var bool
     */
    protected $aliased = false;

    /**
     * Array of parsed data from this annotation.
     *
     * @var array
     */
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
     * @return self
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
     * With an array of data that was output from an Annotation, via `toArray()`, hydrate a new Annotation object.
     *
     * @param array $data
     * @param null|Version $version
     * @return self
     */
    public static function hydrate(array $data = [], Version $version = null)
    {
        $class = get_called_class();

        /** @var Annotation $annotation */
        $annotation = new $class('', $data['class'], $data['method'], $version);

        if ($annotation->requiresVisibilityDecorator()) {
            $annotation->setVisibility($data['visible']);
        }

        if ($annotation->supportsDeprecation()) {
            $annotation->setDeprecated($data['deprecated']);
        }

        if ($annotation->supportsScopes()) {
            $scopes = [];
            foreach ($data['scopes'] as $scope) {
                $scopes[] = ScopeAnnotation::hydrate(array_merge(
                    $scope,
                    [
                        'class' => __CLASS__,
                        'method' => __METHOD__
                    ]
                ));
            }

            $annotation->setScopes($scopes);
        }

        if ($annotation->supportsVendorTags() &&
            (array_key_exists('vendor_tags', $data) && !empty($data['vendor_tags']))
        ) {
            $vendor_tags = [];
            foreach ($data['vendor_tags'] as $vendor_tag) {
                $vendor_tags[] = (new VendorTagAnnotation(
                    $vendor_tag,
                    $data['class'],
                    $data['method'],
                    $version
                ))->process();
            }

            $annotation->setVendorTags($vendor_tags);
        }

        if ($annotation->supportsVersioning() && $version) {
            $annotation->setVersion($version);
        }

        return $annotation;
    }

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
     * Convert the parsed annotation into an array.
     *
     * @return array
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
     * @return self
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
     * @return self
     */
    public function setDeprecated(bool $deprecated): self
    {
        $this->deprecated = $deprecated;
        return $this;
    }

    /**
     * Is this annotation an alias?
     *
     * @return bool
     */
    public function isAliased(): bool
    {
        return $this->aliased;
    }

    /**
     * Set if this annotation is an alias or not.
     *
     * @param bool $aliased
     * @return self
     */
    public function setAliased(bool $aliased): self
    {
        $this->aliased = $aliased;
        return $this;
    }

    /**
     * Set any aliases to this annotation.
     *
     * @param array<Annotation> $aliases
     * @return self
     */
    public function setAliases(array $aliases): self
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Get all available aliases for this annotation.
     *
     * @return array<Annotation>
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Set any required authentication scopes to this annotation.
     *
     * @param array<Annotation> $scopes
     * @return self
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
     * @return self
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
     * @return self
     */
    public function setVersion(Version $version): self
    {
        $this->version = $version;
        return $this;
    }
}
