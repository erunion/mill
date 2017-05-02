<?php
namespace Mill\Parser\Resource\Action;

use Mill\Exceptions\Annotations\MultipleAnnotationsException;
use Mill\Exceptions\Annotations\RequiredAnnotationException;
use Mill\Exceptions\Resource\MissingVisibilityDecoratorException;
use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException;
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
     * @var string|null
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
    public function __construct($class, $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Parse the instance class and method into actionable annotations and documentation.
     *
     * @return Documentation
     * @throws NoAnnotationsException If no annotations were found.
     * @throws RequiredAnnotationException If a required `@api-label` annotation is missing.
     * @throws MultipleAnnotationsException If multiple `@api-label` annotations were found.
     * @throws RequiredAnnotationException If a required `@api-contentType` annotation is missing.
     * @throws MissingVisibilityDecoratorException If an annotation is missing a visibility decorator.
     * @throws RequiredAnnotationException If a required annotation is missing.
     * @throws PublicDecoratorOnPrivateActionException If a `:public` decorator is found on a `:private` action.
     */
    public function parse()
    {
        $parser = new Parser($this->class);
        $annotations = $parser->getAnnotations($this->method);

        if (empty($annotations)) {
            throw NoAnnotationsException::create($this->class, $this->method);
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

        // Verify that we don't have any public annotations on a private action.
        $visibilities = [];

        /** @var \Mill\Parser\Annotation $action */
        foreach ($this->annotations['uri'] as $action) {
            $visibilities[] = ($action->isVisible()) ? 'public' : 'private';
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
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get the class method documented label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get the HTTP method that we're parsing.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the HTTP Content-Type that this action returns content in.
     *
     * @param Version|string|null $version
     * @return string
     */
    public function getContentType($version = null)
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
            if (!$annotation_version) {
                return $annotation->getContentType();
            } elseif ($version && $annotation_version->matches($version)) {
                return $annotation->getContentType();
            }
        }
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
    public function getUris()
    {
        return $this->annotations['uri'];
    }

    /**
     * Get the current URI for this action.
     *
     * @return UriAnnotation
     */
    public function getUri()
    {
        $uris = $this->getUris();
        return array_shift($uris);
    }

    /**
     * Set the lone URI that this action runs under for a specific group.
     *
     * This is used in the Compiler system when grouping actions under groups. If an action runs on the `Me\Videos`
     * and `Users\Videos` groups, we don't want the action in the `Me\Videos` group to have actions with
     * `Users\Videos` URIs.
     *
     * @param \Mill\Parser\Annotations\UriAnnotation $uri
     * @return void
     */
    public function setUri(UriAnnotation $uri)
    {
        $this->annotations['uri'] = [$uri];
    }

    /**
     * Get the raw URI segment annotations that are part of this action.
     *
     * @return array
     */
    public function getUriSegments()
    {
        return (isset($this->annotations['uriSegment'])) ? $this->annotations['uriSegment'] : [];
    }

    /**
     * Set the URI segments that this action has.
     *
     * This is used in the Compiler system when grouping actions under groups. If an action broadcasts on
     * `/me/videos` and `/users/:id/videos`, we don't want the URI segments for `/users/:id/videos` to be a part of the
     * compiled `/me/videos` action.
     *
     * @param array $segments
     * @return void
     */
    public function setUriSegments(array $segments = [])
    {
        $this->annotations['uriSegment'] = $segments;
    }

    /**
     * Get back any application capabilities that this action has set as being required.
     *
     * @return array
     */
    public function getCapabilities()
    {
        return (isset($this->annotations['capability'])) ? $this->annotations['capability'] : [];
    }

    /**
     * Get back any authentication scopes that this action has set as being required.
     *
     * @return array
     */
    public function getScopes()
    {
        return (isset($this->annotations['scope'])) ? $this->annotations['scope'] : [];
    }

    /**
     * Get back any parameters that this action has available.
     *
     * @return array
     */
    public function getParameters()
    {
        return (isset($this->annotations['param'])) ? $this->annotations['param'] : [];
    }

    /**
     * Get back any responses that this action can throw. This will include both returns (`@api-return`) and exceptions
     * (`@api-throws`).
     *
     * @return array
     */
    public function getResponses()
    {
        $return = (isset($this->annotations['return'])) ? $this->annotations['return'] : [];
        $throws = (isset($this->annotations['throws'])) ? $this->annotations['throws'] : [];

        return array_merge($return, $throws);
    }

    /**
     * Get the (absolute) minimum version that this action is supported on.
     *
     * @return Parser\Annotations\MinVersionAnnotation|null
     */
    public function getMinimumVersion()
    {
        return (isset($this->annotations['minVersion'])) ? $this->annotations['minVersion'][0] : null;
    }

    /**
     * Filter down, and return, all annotations on this action to a specific version.
     *
     * @param string $version
     * @return array
     */
    public function filterAnnotationsForVersion($version)
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
     * Convert the parsed resource action documentation into an array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [
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
                if (empty($data['annotations'][$key])) {
                    $data['annotations'][$key] = [];
                }

                /** @var \Mill\Parser\Annotation $annotation */
                $data['annotations'][$key][] = $annotation->toArray();
            }
        }

        return $data;
    }
}
