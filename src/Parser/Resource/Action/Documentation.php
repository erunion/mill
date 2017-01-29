<?php
namespace Mill\Parser\Resource\Action;

use Mill\Exceptions\MultipleAnnotationsException;
use Mill\Exceptions\RequiredAnnotationException;
use Mill\Exceptions\Resource\MissingVisibilityDecoratorException;
use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException;
use Mill\Parser;
use Mill\Parser\Annotations\UriAnnotation;

/**
 * Class for parsing a docblock on a given class and method for resource action documentation.
 *
 */
class Documentation
{
    /**
     * Name of the controller we're parsing.
     *
     * @var string
     */
    protected $controller;

    /**
     * Name of the controller method we're parsing.
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
     * Content type that this action handles content as.
     *
     * @var string
     */
    protected $content_type;

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
     * @param string $controller
     * @param string $method
     */
    public function __construct($controller, $method)
    {
        $this->controller = $controller;
        $this->method = $method;
    }

    /**
     * Parse the instance controller and action into actionable annotations and documentation.
     *
     * @return Documentation
     * @throws NoAnnotationsException If no annotations were found.
     * @throws RequiredAnnotationException If a required `@api-label` annotation is missing.
     * @throws MultipleAnnotationsException If multiple `@api-label` annotations were found.
     * @throws RequiredAnnotationException If a required `@api-contentType` annotation is missing.
     * @throws MultipleAnnotationsException If multiple `@api-contentType` annotations were found.
     * @throws MissingVisibilityDecoratorException If an annotation is missing a visibility decorator.
     * @throws RequiredAnnotationException If a required annotation is missing.
     * @throws PublicDecoratorOnPrivateActionException If a `:public` decorator is found on a `:private` action.
     */
    public function parse()
    {
        $parser = new Parser($this->controller);
        $annotations = $parser->getAnnotations($this->method);

        if (empty($annotations)) {
            throw NoAnnotationsException::create($this->controller, $this->method);
        }

        // Parse out the `@api-label` annotation.
        if (!isset($annotations['label'])) {
            throw RequiredAnnotationException::create('label', $this->controller, $this->method);
        } elseif (count($annotations['label']) > 1) {
            throw MultipleAnnotationsException::create('label', $this->controller, $this->method);
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
            throw RequiredAnnotationException::create('contentType', $this->controller, $this->method);
        } elseif (count($annotations['contentType']) > 1) {
            throw MultipleAnnotationsException::create('contentType', $this->controller, $this->method);
        } else {
            /** @var \Mill\Parser\Annotations\ContentTypeAnnotation $annotation */
            $annotation = reset($annotations['contentType']);
            $this->content_type = $annotation->getContentType();
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
                        $this->controller,
                        $this->method
                    );
                }

                $this->annotations[$key][] = $annotation;
            }
        }

        // Run through the parsed annotations and verify that we aren't missing any required annotations.
        foreach (self::$REQUIRED_ANNOTATIONS as $required) {
            if (!isset($this->annotations[$required])) {
                throw RequiredAnnotationException::create($required, $this->controller, $this->method);
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

                    throw PublicDecoratorOnPrivateActionException::create($key, $this->controller, $this->method);
                }
            }
        }

        return $this;
    }

    /**
     * Get the controller that we're parsing.
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Get the controller method documented label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get the controller HTTP method that we're parsing.
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
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
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
     * Get an array of all actions that this action has been configured against.
     *
     * @param string $since_version
     * @return array
     */
    public function getSupportedVersions($since_version)
    {
        $versions = [
            $since_version
        ];

        foreach ($this->annotations as $name => $data) {
            /** @var Parser\Annotation $annotation */
            foreach ($data as $annotation) {
                if (!$annotation->supportsVersioning()) {
                    continue;
                }

                $version = $annotation->getVersion();
                if (!$version) {
                    continue;
                }

                $versions[] = $version->getStart();
                $versions[] = $version->getEnd();
            }
        }

        $minVersion = $this->getMinimumVersion();
        if (!empty($minVersion)) {
            // If this version has a minimum version, then let's shift everything to start there by removing the catch
            // all "*" version generated by our version parsing engine.
            if (in_array('*', $versions)) {
                $versions = array_flip($versions);
                unset($versions['*']);
                $versions = array_flip($versions);
            }

            $versions[] = $minVersion->getMinimumVersion();
        }

        $versions = array_unique($versions);

        // If we have support for all (*) versions here, then let's drop back out and swap it in with a range of
        // versions between the min/max we've found here.
        if (in_array('*', $versions)) {
            $max = max($versions);
            $versions = range($since_version, $max, .1);
        }

        // Retain all version strings as floats (convert "3" to "3.0").
        array_walk($versions, function (&$version) {
            $version = sprintf('%.1f', $version);
        });

        sort($versions);

        return $versions;
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
            'content_type' => $this->content_type,
            'method' => $this->method,
            'annotations' => []
        ];

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
