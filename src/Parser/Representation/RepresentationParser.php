<?php
namespace Mill\Parser\Representation;

use gossi\docblock\tags\UnknownTag;
use Mill\Container;
use Mill\Exceptions\MethodNotImplementedException;
use Mill\Exceptions\MethodNotSuppliedException;
use Mill\Exceptions\Representation\DuplicateFieldException;
use Mill\Parser;
use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Annotations\ScopeAnnotation;
use Mill\Parser\Version;

/**
 * Class for parsing the docblock on a representation.
 *
 */
class RepresentationParser extends Parser
{
    // http://stackoverflow.com/a/13114141/1886079
    const DOC_PATTERN = '~/\*\*(.*?)\*/~s';

    // Everything between the second asterisk and the first annotation
    const DOC_BODY_MATCH = '~\s*\*\s+(.*)~';

    /**
     * Representation class that we want to parse.
     *
     * @var string
     */
    protected $class;

    /**
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;

        parent::__construct($class);
    }

    /**
     * Locate, and parse, the annotations for a representation method.
     *
     * @param string|null $method_name
     * @return array An array containing all the found annotations.
     * @throws MethodNotSuppliedException If a method is not supplied to parse.
     * @throws MethodNotImplementedException If the supplied method does not exist on the supplied controller.
     */
    public function getAnnotations($method_name = null)
    {
        if (empty($method_name)) {
            throw MethodNotSuppliedException::create($this->class);
        }

        $this->method = $method_name;

        $reader = Container::getRepresentationAnnotationReader();
        $code = $reader($this->class, $this->method);

        $annotations = $this->parse($code);

        if (count($annotations) > 1) {
            // Keep things tidy.
            ksort($annotations);

            // Run through all created annotations and cascade any versioning down into any present child annotations.
            /** @var DataAnnotation $annotation */
            foreach ($annotations as $identifier => $annotation) {
                if (!$annotation->getVersion() && !$annotation->getCapability() && !$annotation->getScopes()) {
                    continue;
                }

                $this->carryAnnotationSettingsToChildren($annotation, $annotations);
            }
        }

        return $annotations;
    }

    /**
     * Parse a group of our custom annotations.
     *
     * @param array $tags
     * @param string $original_content
     * @return array
     */
    public function parseAnnotations(array $tags, $original_content)
    {
        $scopes = [];
        $see_pointers = [];
        $annotations = [];
        $data = [];

        /** @var Version|null $version */
        $version = null;

        // Does this have any `@api-see` pointers or a `@api-version` declaration?
        /** @var UnknownTag $tag */
        foreach ($tags as $tag) {
            $annotation = $this->getAnnotationNameFromTag($tag);
            $content = $tag->getDescription();
            $content = trim($content);

            switch ($annotation) {
                case 'data':
                    $data[] = $content;
                    break;

                case 'scope':
                    $scopes[] = new ScopeAnnotation($content, $this->class, $this->method);
                    break;

                case 'see':
                    $see_pointers = explode(' ', $content);
                    break;

                case 'version':
                    $version = new Version($content, $this->class, $this->method);
                    break;
            }
        }

        foreach ($data as $content) {
            $annotation = new DataAnnotation($content, $this->class, $this->method, $version);
            if (!empty($scopes)) {
                $annotation->setScopes($scopes);
            }

            $annotations[$annotation->getIdentifier()] = $annotation;
        }

        // If we matched an `@api-see` annotation, then let's parse it out into viable annotations.
        if (!empty($see_pointers)) {
            list($see_class, $see_method) = explode('::', array_shift($see_pointers));
            if (in_array(strtolower($see_class), ['self', 'static'])) {
                $see_class = $this->class;
            }

            $prefix = array_shift($see_pointers);

            // Pass in the current array (by reference) of found annotations that we have so we can do depth traversal
            // for version and capability requirements of any implied children, by way of dot-notation.
            $parser = new self($see_class);
            $see_annotations = $parser->getAnnotations($see_method);

            /** @var DataAnnotation $annotation */
            foreach ($see_annotations as $name => $annotation) {
                // If this `@api-see` is being used with an `@api-version`, then the version here should always be
                // applied to any annotations we're including with the `@api-see`.
                //
                // If, however, an annotation we're loading has its own versioning set, we'll combine the pointers
                // version with the annotations version to create a new constraint specifically for that annotation.
                //
                // For example, if `external_urls` is versioned at `>=1.1`, and points to a method to load
                // `external_urls.tickets`, but that's versioned at `<1.1.3`, the new parsed constraint for
                // `external_urls.tickets` will be `>=1.1 <1.1.3`.
                if ($version) {
                    $annotation_version = $annotation->getVersion();
                    if ($annotation_version) {
                        $new_constraint = implode(' ', [
                            $version->getConstraint(),
                            $annotation_version->getConstraint()
                        ]);

                        $updated_version = new Version($new_constraint, $this->class, $this->method);
                        $annotation->setVersion($updated_version);
                    } else {
                        $annotation->setVersion($version);
                    }
                }

                // If this `@api-see` has a prefix to attach to found annotation identifiers, do so.
                if (!empty($prefix)) {
                    $see_annotations[$prefix . '.' . $name] = $annotation->setIdentifierPrefix($prefix);
                    unset($see_annotations[$name]);
                }
            }

            $annotations += $see_annotations;
        }

        return $annotations;
    }

    /**
     * Parse a block of code for representation documentation and return an array of annotations.
     *
     * @param string $code
     * @return array
     * @throws DuplicateFieldException If a found field exists more than once.
     */
    public function parse($code)
    {
        $representation = [];

        if (preg_match_all(self::DOC_PATTERN, $code, $matches)) {
            foreach ($matches[1] as $block) {
                $annotations = $this->parseDocblock($block, false);
                if (empty($annotations)) {
                    continue;
                }

                foreach ($annotations as $field_name => $annotation) {
                    if (isset($representation[$field_name])) {
                        throw DuplicateFieldException::create($field_name, $this->class, $this->method);
                    }

                    $representation[$field_name] = $annotations[$field_name];
                }
            }
        }

        return $representation;
    }

    /**
     * Given a DataAnnotation object, carry any versioning or capabilities on it down to any dot-notation children in
     * an array of other annotations.
     *
     * @param DataAnnotation $parent
     * @param array $annotations
     * @return void
     */
    private function carryAnnotationSettingsToChildren(DataAnnotation $parent, array &$annotations = [])
    {
        $parent_identifier = $parent->getIdentifier();
        $parent_version = $parent->getVersion();

        /** @var array<ScopeAnnotation> $parent_scopes */
        $parent_scopes = $parent->getScopes();

        /** @var string $parent_capability */
        $parent_capability = $parent->getCapability();

        /** @var DataAnnotation $annotation */
        foreach ($annotations as $identifier => $annotation) {
            if ($identifier === $parent_identifier) {
                continue;
            }

            // Is this annotation a child of what we're looking for?
            if ($parent_identifier . '.' !== substr($identifier, 0, strlen($parent_identifier . '.'))) {
                continue;
            }

            if (!empty($parent_version) && !$annotation->getVersion()) {
                $annotation->setVersion($parent_version);
            }

            if (!empty($parent_capability) && !$annotation->getCapability()) {
                $annotation->setCapability($parent_capability);
            }

            if (!empty($parent_scopes) && !$annotation->getScopes()) {
                $annotation->setScopes($parent_scopes);
            }
        }
    }
}
