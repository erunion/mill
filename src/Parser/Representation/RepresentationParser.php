<?php
namespace Mill\Parser\Representation;

use gossi\docblock\tags\UnknownTag;
use Mill\Container;
use Mill\Exceptions\MethodNotImplementedException;
use Mill\Exceptions\MethodNotSuppliedException;
use Mill\Exceptions\Representation\DuplicateFieldException;
use Mill\Parser;
use Mill\Parser\Annotations\DataAnnotation;
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

        // Keep things tidy.
        ksort($annotations);

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
        $has_see = [];
        $annotations = [];

        /** @var string|false $has_content */
        $has_content = false;

        /** @var Version|null $has_version */
        $has_version = null;

        // Does this have any `@api-see` pointers or a `@api-version` declaration?
        /** @var UnknownTag $tag */
        foreach ($tags as $tag) {
            $annotation = $this->getAnnotationNameFromTag($tag);
            $content = $tag->getDescription();
            $content = trim($content);
            //$decorators = null;

            switch ($annotation) {
                case 'data':
                    $has_content = $content;
                    break;

                case 'see':
                    $has_see = explode(' ', $content);
                    break;

                case 'version':
                    $has_version = new Version($content, $this->class, $this->method);
                    break;
            }
        }

        // If we matched an `@api-see` annotation, then let's parse it out into viable annotations.
        if (!empty($has_see)) {
            list($see_class, $see_method) = explode('::', array_shift($has_see));
            $prefix = array_shift($has_see);

            $parser = new self($see_class);
            $see_annotations = $parser->getAnnotations($see_method);

            /** @var DataAnnotation $annotation */
            foreach ($see_annotations as $name => $annotation) {
                if ($has_version) {
                    // If this `@api-see` is being used with a `@api-version`, then the version here should always
                    // take precedence over any versioning set up within the see.
                    $annotation->setVersion($has_version);
                }

                // If this `@api-see` has a prefix to attach to found annotation identifiers, do so.
                if (!empty($prefix)) {
                    $see_annotations[$prefix . '.' . $name] = $annotation->setIdentifierPrefix($prefix);
                    unset($see_annotations[$name]);
                }
            }

            $annotations += $see_annotations;
        }

        // If we don't have any `@api-data` content, then don't bother setting up a DataAnnotation.
        if (empty($has_content)) {
            return $annotations;
        }

        $annotation = new DataAnnotation($has_content, $this->class, $this->method, $has_version);
        $annotations[$annotation->getIdentifier()] = $annotation;

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
}
