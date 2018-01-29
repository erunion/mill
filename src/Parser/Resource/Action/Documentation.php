<?php
namespace Mill\Parser\Resource\Action;

use Mill\Exceptions\Annotations\MultipleAnnotationsException;
use Mill\Exceptions\Annotations\RequiredAnnotationException;
use Mill\Exceptions\Resource\MissingVisibilityDecoratorException;
use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException;
use Mill\Exceptions\Resource\TooManyAliasedUrisException;
use Mill\Parser;
use Mill\Parser\Annotations\UriAnnotation;
use Mill\Parser\Version;

/**
 * Class for parsing a docblock on a given class and method for resource action documentation.
 *
 */
class Documentation
{
    /**
     * Class we're parsing.
     *
     * @var string
     */
    protected $class;

    /**
     * Class method we're parsing.
     *
     * @var string
     */
    protected $method;

    /**
     * Short description/label/title of the action.
     *
     * @var string
     */
    protected $label;

    /**
     * Fuller description of the action. This should normally consist of Markdown.
     *
     * @var null|string
     */
    protected $description = null;

    /**
     * Content types that this action might return. Multiple may be returned because of versioning.
     *
     * @var array
     */
    protected $content_types = [];

    /**
     * Array of parsed annotations that exist on this action.
     *
     * @var array
     */
    protected $annotations = [];

    /**
     * Array of required annotations.
     *
     * @var array
     */
    protected static $REQUIRED_ANNOTATIONS = [
        'uri'
    ];

    /**
     * Array of accepted annotations (excluding those that must have a visibility decorator).
     *
     * @var array
     */
    protected static $ACCEPTED_ANNOTATIONS = [
        'capability',
        'param',
        'minVersion',
        'return',
        'scope',
        'throws',
        'uri',
        'uriSegment'
    ];

    /**
     * @param string $class
     * @param string $method
     */
    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Parse the instance class and method into actionable annotations and documentation.
     *
     * @return self
     * @throws NoAnnotationsException If no annotations were found.
     */
    public function parse(): self
    {
        $parser = new Parser($this->class);
        $annotations = $parser->getAnnotations($this->method);

        if (empty($annotations)) {
            throw NoAnnotationsException::create($this->class, $this->method);
        }

        return $this->parseAnnotations($annotations);
    }

    /**
     * Parse an array of annotation objects and set them to the instance resource action documentation.
     *
     * @param array $annotations
     * @return self
     * @throws MissingVisibilityDecoratorException
     * @throws MultipleAnnotationsException
     * @throws PublicDecoratorOnPrivateActionException
     * @throws RequiredAnnotationException
     * @throws TooManyAliasedUrisException
     */
    public function parseAnnotations(array $annotations = []): self
    {
        // Parse out the `@api-label` annotation.
        if (!isset($annotations['label'])) {
            throw RequiredAnnotationException::create('label', $this->class, $this->method);
        } elseif (count($annotations['label']) > 1) {
            throw MultipleAnnotationsException::create('label', $this->class, $this->method);
        } else {
            /** @var \Mill\Parser\Annotations\LabelAnnotation $annotation */
            $annotation = reset($annotations['label']);
            $this->label = $annotation->getLabel();
        }

        // Parse out the description block, if it's present.
        if (!empty($annotations['description'])) {
            /** @var \Mill\Parser\Annotations\DescriptionAnnotation $annotation */
            $annotation = reset($annotations['description']);
            $this->description = $annotation->getDescription();
        }

        // Parse out the `@api-contentType` annotation.
        if (!isset($annotations['contentType'])) {
            throw RequiredAnnotationException::create('contentType', $this->class, $this->method);
        } else {
            $this->content_types = $annotations['contentType'];
        }

        // Parse out any remaining annotations.
        foreach ($annotations as $key => $data) {
            if (!in_array($key, self::$ACCEPTED_ANNOTATIONS)) {
                continue;
            }

            /** @var \Mill\Parser\Annotation $annotation */
            foreach ($data as $annotation) {
                if ($annotation->requiresVisibilityDecorator() && !$annotation->hasVisibility()) {
                    throw MissingVisibilityDecoratorException::create(
                        $key,
                        $this->class,
                        $this->method
                    );
                }

                // If we're dealing with parameter annotations, let's set us up the ability to later sort them in
                // alphabetical order by keying their annotation array off the parameter field name.
                if ($key === 'param') {
                    /** @var Parser\Annotations\ParamAnnotation $annotation */
                    $this->annotations[$key][$annotation->getField()] = $annotation;
                } else {
                    $this->annotations[$key][] = $annotation;
                }
            }
        }

        // Keep the parameter annotation array in alphabetical order, so they're easier to consume in the documentation.
        if (isset($this->annotations['param'])) {
            ksort($this->annotations['param']);
        }

        // Run through the parsed annotations and verify that we aren't missing any required annotations.
        foreach (self::$REQUIRED_ANNOTATIONS as $required) {
            if (!isset($this->annotations[$required])) {
                throw RequiredAnnotationException::create($required, $this->class, $this->method);
            }
        }

        // Process any URI aliases, and also verify that we don't have any public annotations on a private action.
        $visibilities = [];
        $aliases = [];

        /** @var \Mill\Parser\Annotations\UriAnnotation $uri */
        foreach ($this->annotations['uri'] as $uri) {
            $visibilities[] = ($uri->isVisible()) ? 'public' : 'private';

            if ($uri->isAliased()) {
                $aliases[] = $uri;
            }
        }

        if (!empty($aliases)) {
            if (count($aliases) >= count($this->annotations['uri'])) {
                throw TooManyAliasedUrisException::create($this->class, $this->method);
            }

            /** @var \Mill\Parser\Annotations\UriAnnotation $uri */
            foreach ($this->annotations['uri'] as $uri) {
                if (!$uri->isAliased()) {
                    $uri->setAliases($aliases);
                }
            }
        }

        // If this action has multiple visibilities, then we don't need to bother with these checks.
        $visibilities = array_unique($visibilities);
        if (count($visibilities) > 1) {
            return $this;
        } elseif (in_array('private', $visibilities)) {
            foreach ($this->annotations as $key => $data) {
                if ($key === 'uri') {
                    continue;
                }

                /** @var \Mill\Parser\Annotation $annotation */
                foreach ($data as $annotation) {
                    if (!$annotation->requiresVisibilityDecorator()) {
                        continue;
                    } elseif (!$annotation->isVisible()) {
                        continue;
                    }

                    throw PublicDecoratorOnPrivateActionException::create($key, $this->class, $this->method);
                }
            }
        }

        return $this;
    }

    /**
     * Get the class that we're parsing.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the class method documented label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get the HTTP method that we're parsing.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get all annotations that this action has been configured with.
     *
     * @return array
     */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }

    /**
     * Get the HTTP Content-Types that have been set up for this action, regardless of their version.
     *
     * @return array
     */
    public function getContentTypes(): array
    {
        return $this->content_types;
    }

    /**
     * @param array $content_types
     * @return self
     */
    public function setContentTypes(array $content_types): self
    {
        $this->content_types = $content_types;
        return $this;
    }

    /**
     * Get the HTTP Content-Type that this action returns content in.
     *
     * @param null|string|Version $version
     * @return string
     * @throws \Exception
     */
    public function getContentType($version = null): string
    {
        if ($version instanceof Version) {
            $version = $version->getConstraint();
        }

        if (!$version) {
            return $this->content_types[0]->getContentType();
        }

        /** @var Parser\Annotations\ContentTypeAnnotation $annotation */
        foreach ($this->content_types as $annotation) {
            /** @var Version $annotation_version */
            $annotation_version = $annotation->getVersion();
            if (!$annotation_version || ($version && $annotation_version->matches($version))) {
                return $annotation->getContentType();
            }
        }

        throw new \Exception('An unexpected error occurred while retrieving a content type. This should never happen!');
    }

    /**
     * Get the description of this action.
     *
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the raw URI annotations that are part of this action.
     *
     * @return array
     */
    public function getUris(): array
    {
        return $this->annotations['uri'];
    }

    /**
     * Get the current URI for this action.
     *
     * @return UriAnnotation
     */
    public function getUri(): UriAnnotation
    {
        $uris = $this->getUris();
        return array_shift($uris);
    }

    /**
     * Set the lone URI that this action runs under for a specific namespace.
     *
     * This is used in the Compiler system when grouping actions under namespaces. If an action runs on the `Me\Videos`
     * and `Users\Videos` namespaces, we don't want the action in the `Me\Videos` namespace to have actions with
     * `Users\Videos` URIs.
     *
     * @param UriAnnotation $uri
     */
    public function setUri(UriAnnotation $uri): void
    {
        $this->annotations['uri'] = [$uri];
    }

    /**
     * Get a combined array of the resource action URI and any URI aliases that it might have.
     *
     * @return array
     */
    public function getUriAndAliases()
    {
        $uri = $this->getUri();
        $uris = array_merge([
            $uri->getCleanPath() => $uri
        ], $this->getUriAliases());

        ksort($uris);

        return $uris;
    }

    /**
     * Get a path-keyed array of any URI aliases that this action might have.
     *
     * @return array
     */
    public function getUriAliases()
    {
        $aliases = [];

        /** @var UriAnnotation $alias */
        foreach ($this->getUri()->getAliases() as $alias) {
            $aliases[$alias->getCleanPath()] = $alias;
        }

        return $aliases;
    }

    /**
     * Get the raw URI segment annotations that are part of this action.
     *
     * @return array
     */
    public function getUriSegments(): array
    {
        return (isset($this->annotations['uriSegment'])) ? $this->annotations['uriSegment'] : [];
    }

    /**
     * Set the URI segments that this action has.
     *
     * This is used in the Compiler system when grouping actions under namespaces. If an action broadcasts on
     * `/me/videos` and `/users/:id/videos`, we don't want the URI segments for `/users/:id/videos` to be a part of the
     * compiled `/me/videos` action.
     *
     * @param array $segments
     */
    public function setUriSegments(array $segments = []): void
    {
        $this->annotations['uriSegment'] = $segments;
    }

    /**
     * Get back any application capabilities that this action has set as being required.
     *
     * @return array
     */
    public function getCapabilities(): array
    {
        return (isset($this->annotations['capability'])) ? $this->annotations['capability'] : [];
    }

    /**
     * Get back any authentication scopes that this action has set as being required.
     *
     * @return array
     */
    public function getScopes(): array
    {
        return (isset($this->annotations['scope'])) ? $this->annotations['scope'] : [];
    }

    /**
     * Get back any parameters that this action has available.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return (isset($this->annotations['param'])) ? $this->annotations['param'] : [];
    }

    /**
     * Get back any responses that this action can throw. This will include both returns (`@api-return`) and exceptions
     * (`@api-throws`).
     *
     * @return array
     */
    public function getResponses(): array
    {
        $return = (isset($this->annotations['return'])) ? $this->annotations['return'] : [];
        $throws = (isset($this->annotations['throws'])) ? $this->annotations['throws'] : [];

        return array_merge($return, $throws);
    }

    /**
     * Get the (absolute) minimum version that this action is supported on.
     *
    /**
     * @return null|Parser\Annotations\MinVersionAnnotation
     */
    public function getMinimumVersion(): ?Parser\Annotations\MinVersionAnnotation
    {
        return (isset($this->annotations['minVersion'])) ? $this->annotations['minVersion'][0] : null;
    }

    /**
     * Filter down, and return, all annotations on this action to a specific version.
     *
     * @param string $version
     * @return array
     */
    public function filterAnnotationsForVersion(string $version): array
    {
        foreach ($this->annotations as $name => $data) {
            /** @var Parser\Annotation $annotation */
            foreach ($data as $k => $annotation) {
                // If this annotation has a set version, but that version doesn't match what we're looking for, filter
                // it out.
                $annotation_version = $annotation->getVersion();
                if ($annotation_version) {
                    if (!$annotation_version->matches($version)) {
                        unset($this->annotations[$name][$k]);
                    }
                }
            }
        }

        return $this->annotations;
    }

    /**
     * Filter down, and return, all annotations on this action that match a specific visibility. This can either be
     * public, public+private, or capability-locked.
     *
     * @psalm-suppress RedundantCondition
     * @param bool $allow_private
     * @param array|null $only_capabilities
     * @return array
     */
    public function filterAnnotationsForVisibility(bool $allow_private, ?array $only_capabilities): array
    {
        if ($allow_private && empty($only_capabilities)) {
            return $this->annotations;
        }

        $method_capabilities = $this->getCapabilities();

        foreach ($this->annotations as $name => $data) {
            /** @var Parser\Annotation $annotation */
            foreach ($data as $k => $annotation) {
                // While URI annotations are already filtered within the generator, so we don't need to further filter
                // them out, we do need to filter URI aliases as those can have their independent visibilities.
                if ($annotation instanceof UriAnnotation) {
                    $aliases = $annotation->getAliases();
                    if (!empty($aliases)) {
                        /** @var UriAnnotation $alias */
                        foreach ($aliases as $k => $alias) {
                            // If this annotation isn't visible, and we don't want private documentation, filter it out.
                            if (!$allow_private && $alias->hasVisibility() && !$alias->isVisible()) {
                                unset($aliases[$k]);
                            }
                        }

                        $annotation->setAliases($aliases);
                    }

                    continue;
                }

                // If this annotation has a capability, but that capability isn't in the set of capabilities we're
                // generating documentation for, filter it out.
                $capability = $annotation->getCapability();
                if (!empty($capability) || !empty($method_capabilities)) {
                    // If we don't even have capabilities to look for, then filter this annotation out completely.
                    if (!is_null($only_capabilities) && empty($only_capabilities)) {
                        unset($this->annotations[$name][$k]);
                        continue;
                    }

                    $all_found = true;

                    /** @var Parser\Annotations\CapabilityAnnotation $method_capability */
                    foreach ($method_capabilities as $method_capability) {
                        /** @var string $capability */
                        $capability = $method_capability->getCapability();
                        if (!is_null($only_capabilities) && !in_array($capability, $only_capabilities)) {
                            $all_found = false;
                        }
                    }

                    if (!$all_found ||
                        (
                            !empty($capability) &&
                            (!is_null($only_capabilities) && !in_array($capability, $only_capabilities))
                        )
                    ) {
                        unset($this->annotations[$name][$k]);
                        continue;
                    }

                    // Capabilities override individual annotation visibility.
                    continue;
                }

                // If this annotation isn't visible, and we don't want private documentation, filter it out.
                if (!$allow_private && $annotation->hasVisibility() && !$annotation->isVisible()) {
                    unset($this->annotations[$name][$k]);
                }
            }
        }

        return $this->annotations;
    }

    /**
     * Hydrate a new resource action documentation object with an array of data generated from `toArray`.
     *
     * @param array $data
     * @return self
     */
    public static function hydrate(array $data): self
    {
        $annotations = [];
        $parser = new Parser($data['class']);
        $action = new self($data['class'], $data['method']);

        $annotations['label'][] = $parser->hydrateAnnotation('label', $data['class'], $data['method'], $data);

        if (!empty($data['description'])) {
            $annotations['description'][] = $parser->hydrateAnnotation(
                'description',
                $data['class'],
                $data['method'],
                $data
            );
        }

        foreach ($data['content_types'] as $content_type) {
            $annotations['contentType'][] = $parser->hydrateAnnotation(
                'content_type',
                $data['class'],
                $data['method'],
                $content_type
            );
        }

        foreach ($data['annotations'] as $name => $datas) {
            foreach ($datas as $annotation_data) {
                $annotations[$name][] = $parser->hydrateAnnotation(
                    $name,
                    $data['class'],
                    $data['method'],
                    $annotation_data
                );
            }
        }

        return $action->parseAnnotations($annotations);
    }

    /**
     * Convert the parsed resource action documentation into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'class' => $this->class,
            'label' => $this->label,
            'description' => $this->description,
            'content_types' => [],
            'method' => $this->method,
            'annotations' => []
        ];

        foreach ($this->content_types as $content_type) {
            /** @var \Mill\Parser\Annotations\ContentTypeAnnotation */
            $data['content_types'][] = $content_type->toArray();
        }

        foreach ($this->annotations as $key => $annotations) {
            foreach ($annotations as $annotation) {
                if (!isset($data['annotations'][$key])) {
                    $data['annotations'][$key] = [];
                }

                /** @var \Mill\Parser\Annotation $annotation */
                $data['annotations'][$key][] = $annotation->toArray();
            }
        }

        return $data;
    }
}
