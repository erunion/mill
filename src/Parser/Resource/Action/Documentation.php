<?php
namespace Mill\Parser\Resource\Action;

use Dflydev\DotAccessData\Data;
use Mill\Application;
use Mill\Contracts\Arrayable;
use Mill\Exceptions\Annotations\MultipleAnnotationsException;
use Mill\Exceptions\Annotations\RequiredAnnotationException;
use Mill\Exceptions\Resource\MissingVisibilityDecoratorException;
use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException;
use Mill\Exceptions\Resource\TooManyAliasedPathsException;
use Mill\Parser;
use Mill\Parser\Annotations\PathAnnotation;
use Mill\Parser\Version;

class Documentation implements Arrayable
{
    /** @var array Array of required annotations. */
    const REQUIRED_ANNOTATIONS = [
        'path'
    ];

    /**
     * Array of accepted annotations (excluding those that must have a visibility decorator, or have special handling).
     *
     * @var array
     */
    const ACCEPTED_ANNOTATIONS = [
        'error',
        'param',
        'maxversion',
        'minversion',
        'queryparam',
        'return',
        'scope',
        'path',
        'pathparam',
        'vendortag'
    ];

    /**
     * @psalm-var class-string
     * @var string Class we're parsing.
     */
    protected $class;

    /** @var string Class method we're parsing. */
    protected $method;

    /** @var Application */
    protected $application;

    /** @var string A unique operation ID. */
    protected $operation_id;

    /** @var string Short description/label/title of the action. */
    protected $label;

    /** @var null|string Fuller description of the action. This should normally consist of Markdown. */
    protected $description = null;

    /** @var string Group that this action belongs to. Used for grouping compiled documentation. */
    protected $group;

    /** @var array Content types that this action might return. Multiple may be returned because of versioning. */
    protected $content_types = [];

    /** @var array Array of parsed annotations that exist on this action. */
    protected $annotations = [];

    /**
     * @psalm-param class-string $class
     * @param string $class
     * @param string $method
     * @param Application $application
     */
    public function __construct(string $class, string $method, Application $application)
    {
        $this->class = $class;
        $this->method = $method;
        $this->application = $application;
    }

    /**
     * Parse the instance class and method into actionable annotations and documentation.
     *
     * @return Documentation
     * @throws MissingVisibilityDecoratorException
     * @throws MultipleAnnotationsException
     * @throws NoAnnotationsException
     * @throws PublicDecoratorOnPrivateActionException
     * @throws RequiredAnnotationException
     * @throws TooManyAliasedPathsException
     * @throws \Mill\Exceptions\Resource\UnsupportedDecoratorException
     */
    public function parse(): self
    {
        $parser = new Parser($this->class, $this->application);
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
     * @return Documentation
     * @throws MissingVisibilityDecoratorException
     * @throws MultipleAnnotationsException
     * @throws PublicDecoratorOnPrivateActionException
     * @throws RequiredAnnotationException
     * @throws TooManyAliasedPathsException
     */
    public function parseAnnotations(array $annotations = []): self
    {
        // Parse out the `@api-operationid` annotation.
        if (!isset($annotations['operationid'])) {
            throw RequiredAnnotationException::create('operationid', $this->class, $this->method);
        } elseif (count($annotations['operationid']) > 1) {
            throw MultipleAnnotationsException::create('operationid', $this->class, $this->method);
        } else {
            /** @var \Mill\Parser\Annotations\OperationIdAnnotation $annotation */
            $annotation = reset($annotations['operationid']);
            $this->operation_id = $annotation->getOperationId();
        }

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

        // Parse out the `@api-group` annotation.
        if (!isset($annotations['group'])) {
            throw RequiredAnnotationException::create('group', $this->class, $this->method);
        } elseif (count($annotations['group']) > 1) {
            throw MultipleAnnotationsException::create('group', $this->class, $this->method);
        } else {
            /** @var \Mill\Parser\Annotations\GroupAnnotation $annotation */
            $annotation = reset($annotations['group']);
            $this->group = $annotation->getGroup();
        }

        // Parse out the `@api-contenttype` annotation.
        if (!isset($annotations['contenttype'])) {
            throw RequiredAnnotationException::create('contenttype', $this->class, $this->method);
        } else {
            $this->content_types = $annotations['contenttype'];
        }

        // Parse out any remaining annotations.
        foreach ($annotations as $key => $data) {
            if (!in_array($key, self::ACCEPTED_ANNOTATIONS)) {
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
                if (in_array($key, ['param', 'pathparam', 'queryparam'])) {
                    /** @var Parser\Annotations\ParamAnnotation|Parser\Annotations\QueryParamAnnotation $annotation */
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

        if (isset($this->annotations['pathparam'])) {
            ksort($this->annotations['pathparam']);
        }

        if (isset($this->annotations['queryparam'])) {
            ksort($this->annotations['queryparam']);
        }

        // Run through the parsed annotations and verify that we aren't missing any required annotations.
        foreach (self::REQUIRED_ANNOTATIONS as $required) {
            if (!isset($this->annotations[$required])) {
                throw RequiredAnnotationException::create($required, $this->class, $this->method);
            }
        }

        // Process any path aliases, and also verify that we don't have any public annotations on a private action.
        $visibilities = [];
        $aliases = [];

        /** @var \Mill\Parser\Annotations\PathAnnotation $path */
        foreach ($this->annotations['path'] as $path) {
            $visibilities[] = ($path->isVisible()) ? 'public' : 'private';

            if ($path->isAliased()) {
                $aliases[] = $path;
            }
        }

        if (!empty($aliases)) {
            if (count($aliases) >= count($this->annotations['path'])) {
                throw TooManyAliasedPathsException::create($this->class, $this->method);
            }

            /** @var \Mill\Parser\Annotations\PathAnnotation $path */
            foreach ($this->annotations['path'] as $path) {
                if (!$path->isAliased()) {
                    $path->setAliases($aliases);
                }
            }
        }

        // If this action has multiple visibilities, then we don't need to bother with these checks.
        $visibilities = array_unique($visibilities);
        if (count($visibilities) > 1) {
            return $this;
        } elseif (in_array('private', $visibilities)) {
            foreach ($this->annotations as $key => $data) {
                if ($key === 'path') {
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
     * Get the action operation ID.
     *
     * @return string
     */
    public function getOperationId(): string
    {
        return $this->operation_id;
    }

    /**
     * Increment the current action operation ID. This is used to enforce uniqueness of the operation ID across aliased
     * paths.
     *
     * @param int $increment
     * @return Documentation
     */
    public function incrementOperationId(int $increment): self
    {
        $this->operation_id .= '_alt' . $increment;
        return $this;
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
     * Get the description of this action.
     *
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the group that this action belongs to.
     *
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
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
     * @return Documentation
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
            $annotation_version = $annotation->getVersion();
            if (!$annotation_version || $annotation_version->matches($version)) {
                return $annotation->getContentType();
            }
        }

        throw new \Exception('An unexpected error occurred while retrieving a content type. This should never happen!');
    }

    /**
     * Get the raw path annotations that are part of this action.
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->annotations['path'];
    }

    /**
     * Get the current path for this action.
     *
     * @return PathAnnotation
     */
    public function getPath(): PathAnnotation
    {
        $paths = [];

        /** @var PathAnnotation $path */
        foreach ($this->getPaths() as $k => $path) {
            if (!$path->isAliased()) {
                $paths[] = $path;
            }
        }

        if (empty($paths)) {
            if (count($this->getPaths()) > 1) {
                throw new \Exception(
                    sprintf(
                        'There were zero non-aliased paths detected in this %s::%s.',
                        $this->getClass(),
                        $this->getMethod()
                    )
                );
            }

            $paths = $this->getPaths();
        }

        return array_shift($paths);
    }

    /**
     * Set the lone path that this action runs under for a specific group.
     *
     * This is used in the Compiler system when grouping actions under groups. If an action runs on the `Me\Videos`
     * and `Users\Videos` groups, we don't want the action in the `Me\Videos` group to have actions with `Users\Videos`
     * URIs.
     *
     * @param PathAnnotation $path
     */
    public function setPath(PathAnnotation $path): void
    {
        $this->annotations['path'] = [$path];
    }

    /**
     * Get a combined array of the resource action path and any path aliases that it might have.
     *
     * @return array
     */
    public function getPathAndAliases()
    {
        $path = $this->getPath();
        $paths = array_merge([
            $path->getCleanPath() => $path
        ], $this->getPathAliases());

        ksort($paths);

        return $paths;
    }

    /**
     * Get a path-keyed array of any path aliases that this action might have.
     *
     * @return array
     */
    public function getPathAliases()
    {
        $aliases = [];

        /** @var PathAnnotation $alias */
        foreach ($this->getPath()->getAliases() as $alias) {
            $aliases[$alias->getCleanPath()] = $alias;
        }

        return $aliases;
    }

    /**
     * Get the raw path param annotations that are part of this action.
     *
     * @return array
     */
    public function getPathParameters(): array
    {
        return (isset($this->annotations['pathparam'])) ? $this->annotations['pathparam'] : [];
    }

    /**
     * Set the path params that this action has.
     *
     * This is used in the Compiler system when grouping actions under groups. If an action broadcasts on `/me/videos`
     * and `/users/:id/videos`, we don't want the params for `/users/:id/videos` to be a part of the compiled
     * `/me/videos` action.
     *
     * @param array $params
     */
    public function setPathParams(array $params = []): void
    {
        $this->annotations['pathparam'] = $params;
    }

    /**
     * Get back any application vendor tags that this action has set.
     *
     * @return array
     */
    public function getVendorTags(): array
    {
        return (isset($this->annotations['vendortag'])) ? $this->annotations['vendortag'] : [];
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
     * Get back any query parameters that this action has available.
     *
     * @return array
     */
    public function getQueryParameters(): array
    {
        return (isset($this->annotations['queryparam'])) ? $this->annotations['queryparam'] : [];
    }

    /**
     * Get back all parameters that this action has available.
     *
     * @return array
     */
    public function getAllParameters(): array
    {
        return array_merge($this->getPathParameters(), $this->getParameters(), $this->getQueryParameters());
    }

    /**
     * Get back any responses that this action can throw. This will include both returns (`@api-return`) and exceptions
     * (`@api-error`).
     *
     * @return array
     */
    public function getResponses(): array
    {
        $return = (isset($this->annotations['return'])) ? $this->annotations['return'] : [];
        $error = (isset($this->annotations['error'])) ? $this->annotations['error'] : [];

        return array_merge($return, $error);
    }

    /**
     * Get the (absolute) minimum version that this action is supported on.
     *
     * @return null|Parser\Annotations\MinVersionAnnotation
     */
    public function getMinimumVersion(): ?Parser\Annotations\MinVersionAnnotation
    {
        return (isset($this->annotations['minversion'])) ? $this->annotations['minversion'][0] : null;
    }

    /**
     * Get the (absolute) maximum version that this action is supported on.
     *
     * @return null|Parser\Annotations\MaxVersionAnnotation
     */
    public function getMaximumVersion(): ?Parser\Annotations\MaxVersionAnnotation
    {
        return (isset($this->annotations['maxversion'])) ? $this->annotations['maxversion'][0] : null;
    }

    /**
     * Does this action fall within the bounds of a version?
     *
     * @param string $version
     * @return bool
     */
    public function fallsWithinVersion(string $version): bool
    {
        $min_version = $this->getMinimumVersion();
        $max_version = $this->getMaximumVersion();
        if ($min_version && !$min_version->matches($version) || $max_version && !$max_version->matches($version)) {
            return false;
        }

        return true;
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
     * public, public+private, or vendor tagged.
     *
     * @psalm-suppress RedundantCondition
     * @param bool $allow_private
     * @param array|null $only_vendor_tags
     * @return array
     */
    public function filterAnnotationsForVisibility(bool $allow_private, ?array $only_vendor_tags): array
    {
        if ($allow_private && empty($only_vendor_tags)) {
            return $this->annotations;
        }

        $method_vendor_tags = $this->getVendorTags();

        foreach ($this->annotations as $name => $data) {
            /** @var Parser\Annotation $annotation */
            foreach ($data as $k => $annotation) {
                // While URI annotations are already filtered within the compiler, so we don't need to further filter
                // them out, we do need to filter URI aliases as those can have their independent visibilities.
                if ($annotation instanceof PathAnnotation) {
                    $aliases = $annotation->getAliases();
                    if (!empty($aliases)) {
                        /** @var PathAnnotation $alias */
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

                // If this annotation has vendor tags, but those vendor tags aren't in the set of vendor tags we're
                // compiling documentation for, filter it out.
                $vendor_tags = $annotation->getVendorTags();
                if (!empty($vendor_tags) || !empty($method_vendor_tags)) {
                    // If we don't even have vendor tags to look for, then filter this annotation out completely.
                    if (!is_null($only_vendor_tags) && empty($only_vendor_tags)) {
                        unset($this->annotations[$name][$k]);
                        continue;
                    }

                    $all_found = true;

                    /** @var Parser\Annotations\VendorTagAnnotation $method_vendor_tag */
                    foreach ($method_vendor_tags as $method_vendor_tag) {
                        $vendor_tag = $method_vendor_tag->getVendorTag();
                        if (!is_null($only_vendor_tags) && !in_array($vendor_tag, $only_vendor_tags)) {
                            $all_found = false;
                        }
                    }

                    if (!$all_found) {
                        unset($this->annotations[$name][$k]);
                        continue;
                    }

                    // Vendor tag requirements override individual annotation visibility.
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
     * Convert the parsed parameter documentation content dot notation field names into an exploded array.
     *
     * @return array
     */
    public function getExplodedParameterDotNotation(): array
    {
        return $this->buildExplodedDotNotation($this->getParameters());
    }

    /**
     * Convert the parsed query parameter documentation content dot notation field names into an exploded array.
     *
     * @return array
     */
    public function getExplodedQueryParameterDotNotation(): array
    {
        return $this->buildExplodedDotNotation($this->getQueryParameters());
    }

    /**
     * Convert the parsed path parameter documentation content dot notation field names into an exploded array.
     *
     * @return array
     */
    public function getExplodedPathParameterDotNotation(): array
    {
        return $this->buildExplodedDotNotation($this->getPathParameters());
    }

    /**
     * Convert all parsed parameter (query and normal parameter) documentation content dot notation field names into an
     * exploded array.
     *
     * @return array
     */
    public function getExplodedAllQueryParameterDotNotation(): array
    {
        return $this->buildExplodedDotNotation(array_merge(
            $this->getParameters(),
            $this->getQueryParameters()
        ));
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function buildExplodedDotNotation($parameters = []): array
    {
        if (empty($parameters)) {
            return [];
        }

        $content = new Data();

        /** @var Parser\Annotations\ParamAnnotation|Parser\Annotations\QueryParamAnnotation $parameter */
        foreach ($parameters as $name => $parameter) {
            $content->set($name, [
                Application::DOT_NOTATION_ANNOTATION_DATA_KEY => $parameter->toArray()
            ]);
        }

        return $content->export();
    }

    /**
     * {{@inheritdoc}}
     */
    public function toArray(): array
    {
        $data = [
            'class' => $this->class,
            'label' => $this->label,
            'description' => $this->description,
            'group' => $this->group,
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
