<?php
namespace Mill\Parser;

use Mill\Exceptions\Annotations\InvalidMSONSyntaxException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\CapabilityAnnotation;
use Mill\Parser\Annotations\ScopeAnnotation;

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
     * Does this annotation support aliasing?
     *
     * @return bool
     */
    const SUPPORTS_ALIASING = false;

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
     * Does this annotation support auth token scopes?
     *
     * @return bool|null
     */
    const SUPPORTS_SCOPES = null;

    /**
     * Does this annotation support versioning?
     *
     * @return bool|null
     */
    const SUPPORTS_VERSIONING = null;

    /**
     * @param string $doc
     * @param string $class
     * @param string|null $method
     * @param \Mill\Parser\Version|null $version
     */
    public function __construct($doc, $class, $method, Version $version = null)
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
     * @return mixed
     */
    public function process()
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
     * With an array of data that was output from an Annotation, via `toArray()`, hydrate a new Annotation object.
     *
     * @param array $data
     * @param Version|null $version
     * @return self
     */
    public static function hydrate(array $data = [], Version $version = null)
    {
        $class = get_called_class();

        /** @var Annotation $annotation */
        $annotation = new $class('', $data['class'], $data['method'], $version);

        if (array_key_exists('capability', $data) && !empty($data['capability'])) {
            // Since capability annotations have a `capability` value, let's avoid created a CapabilityAnnotation within
            // another CapabilityAnnotation.
            if ($annotation instanceof CapabilityAnnotation) {
                $capability = $data['capability'];
            } else {
                $capability = (new CapabilityAnnotation(
                    $data['capability'],
                    $data['class'],
                    $data['method'],
                    $version
                ))->process();
            }

            $annotation->setCapability($capability);
        }

        if ($annotation->requiresVisibilityDecorator()) {
            $annotation->setVisibility($data['visible']);
        }

        if ($annotation->supportsAliasing()) {
            $annotation->setAliased($data['aliased']);
            $annotation->setAliases($data['aliases']);
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
    public function requiresVisibilityDecorator()
    {
        return static::REQUIRES_VISIBILITY_DECORATOR;
    }

    /**
     * Does this annotation support aliasing?
     *
     * @return bool
     */
    public function supportsAliasing()
    {
        return static::SUPPORTS_ALIASING;
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
     * Does this annotation support authentication scopes?
     *
     * @return bool
     */
    public function supportsScopes()
    {
        return static::SUPPORTS_SCOPES;
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
     * Convert the parsed annotation into an array.
     *
     * @return array
     */
    public function toArray()
    {
        $arr = [];
        foreach ($this->arrayable as $var) {
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

        // If this annotation supports aliasing, then we should include any aliasing data about it.
        if ($this->supportsAliasing()) {
            $arr['aliased'] = $this->isAliased();
            $arr['aliases'] = [];

            /** @var Annotation $alias */
            foreach ($this->getAliases() as $alias) {
                $arr['aliases'][] = $alias->toArray();
            }
        }

        // If this annotation supports deprecation, then we should include its designation.
        if ($this->supportsDeprecation()) {
            $arr['deprecated'] = $this->isDeprecated();
        }

        // If this annotation supports authentication scopes, then we should include those scopes.
        if ($this->supportsScopes()) {
            $arr['scopes'] = [];

            /** @var Annotation $scope */
            foreach ($this->getScopes() as $scope) {
                $arr['scopes'][] = $scope->toArray();
            }
        }

        // If this annotation supports versioning, then we should include its version
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
     * Is this annotation an alias?
     *
     * @return bool
     */
    public function isAliased()
    {
        return $this->aliased;
    }

    /**
     * Set if this annotation is an alias or not.
     *
     * @param bool $aliased
     * @return Annotation
     */
    public function setAliased($aliased)
    {
        $this->aliased = $aliased;
        return $this;
    }

    /**
     * Set any aliases to this annotation.
     *
     * @param array<Annotation> $aliases
     * @return $this
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Get all available aliases for this annotation.
     *
     * @return array<Annotation>
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Set any required authentication scopes to this annotation.
     *
     * @param array<Annotation> $scopes
     * @return $this
     */
    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * Get all required authentication scopes for this annotation.
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Return the capability, if any, that has been set.
     *
     * @return string|bool
     */
    public function getCapability()
    {
        return $this->capability;
    }

    /**
     * Set a capability that this annotation requires. This is specifically used in tandem with representation depth
     * parsing.
     *
     * @param string|bool $capability
     * @return Annotation
     */
    public function setCapability($capability)
    {
        $this->capability = $capability;
        return $this;
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
     * Set a version that this annotation is available on. This is specifically used in tandem with representation
     * depth parsing.
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
