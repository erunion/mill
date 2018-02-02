<?php
namespace Mill;

use gossi\docblock\Docblock;
use gossi\docblock\tags\UnknownTag;
use Mill\Exceptions\Resource\UnsupportedDecoratorException;
use Mill\Parser\Annotation;
use Mill\Parser\MSON;
use Mill\Parser\Version;
use ReflectionClass;

/**
 * Class for tokenizing a docblock on a given class or method.
 *
 */
class Parser
{
    const REGEX_DECORATOR = '/^(?P<decorator>(:\w+)+)?/u';

    /**
     * The current class that we're going to be parsing.
     *
     * @var string
     */
    protected $class;

    /**
     * The current class method that we're parsing. Used to give better error messaging.
     *
     * @var null|string
     */
    protected $method;

    /**
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * Get an array of HTTP (GET, POST, PUT, PATCH, DELETE) methods that are implemented on the current class.
     *
     * @return array
     */
    public function getHttpMethods()
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        $reflection = new ReflectionClass($this->class);

        $valid_methods = [];
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method)) {
                $valid_methods[] = $method;
            }
        }

        return $valid_methods;
    }

    /**
     * Locate, and parse, the annotations for a class or method.
     *
     * @param null|string $method_name
     * @return array An array containing all the found annotations.
     */
    public function getAnnotations(string $method_name = null): array
    {
        if (!empty($method_name)) {
            $this->method = $method_name;
        }

        $reader = Container::getAnnotationReader();
        $comments = $reader($this->class, $method_name);

        if (empty($comments)) {
            return [];
        }

        return $this->parseDocblock($comments);
    }

    /**
     * Parse a docblock comment into its parts.
     *
     * @link https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     * @param string $docblock
     * @param boolean $parse_description If we want to parse out an unstructured `description` annotation.
     * @return array Array of parsed annotations.
     */
    protected function parseDocblock(string $docblock, bool $parse_description = true): array
    {
        $original_docblock = $docblock;
        $annotations = [];
        $matches = null;

        $parser = self::getAnnotationsFromDocblock($docblock);
        $tags = $parser->getTags();
        if (!empty($tags)) {
            $annotation_tags = [];

            /** @var UnknownTag $tag */
            foreach ($tags as $tag) {
                // If this isn't a Mill annotation, then ignore it.
                $annotation = $tag->getTagName();
                if (substr($annotation, 0, 4) !== 'api-') {
                    continue;
                }

                $annotation_tags[] = $tag;
            }

            $annotations = $this->parseAnnotations($annotation_tags, $original_docblock);
        }

        // Only parse out a `description` annotation if we need to (like in the instance of not parsing a
        // representation).
        if (!$parse_description) {
            return $annotations;
        }

        // Reconstruct the description as the developer wrote it.
        $description = implode("\n\n", array_filter([
            $parser->getShortDescription(),
            $parser->getLongDescription()
        ]));

        if (!empty($description)) {
            $annotations['description'][] = $this->buildAnnotation('description', null, $description);
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
    protected function parseAnnotations(array $tags, string $original_content): array
    {
        $annotations = [];
        $version = null;

        /** @var \gossi\docblock\tags\UnknownTag $tag */
        foreach ($tags as $tag) {
            $annotation = $this->getAnnotationNameFromTag($tag);
            $content = $tag->getDescription();
            $decorators = null;

            preg_match_all(self::REGEX_DECORATOR, $content, $matches);
            if (!empty($matches['decorator'][0])) {
                $decorators = $matches['decorator'][0];
                $content = preg_replace(self::REGEX_DECORATOR, '', $content);
            }

            $content = trim($content);
            switch ($annotation) {
                // Handle the `@api-version` annotation block.
                case 'version':
                    /** @var string $method */
                    $method = $this->method;
                    $version = new Version($content, $this->class, $method);
                    break;

                // Parse all other annotations.
                default:
                    $annotations[$annotation][] = $this->buildAnnotation(
                        $annotation,
                        $decorators,
                        $content,
                        $version
                    );
            }
        }

        return $annotations;
    }

    /**
     * Hydrate an annotation with some data.
     *
     * @param string $name
     * @param string $class
     * @param string $method
     * @param array $data
     * @return Annotation
     */
    public function hydrateAnnotation(string $name, string $class, string $method, array $data = []): Annotation
    {
        $annotation_class = $this->getAnnotationClass(str_replace('_', '', $name));

        $version = null;
        if (!empty($data['version'])) {
            $version = new Version($data['version'], $class, $method);
        }

        return $annotation_class::hydrate(
            array_merge(
                $data,
                [
                    'class' => $class,
                    'method' => $method
                ]
            ),
            $version
        );
    }

    /**
     * Build up an array of annotation data.
     *
     * @param string $name
     * @param null|string $decorators
     * @param string $content
     * @param null|Version $version
     * @return Annotation
     * @throws UnsupportedDecoratorException If an unsupported decorator is found on an annotation.
     */
    private function buildAnnotation(
        string $name,
        ?string $decorators,
        string $content,
        Version $version = null
    ): Annotation {
        $class = $this->getAnnotationClass($name);

        // If this annotation class does not support MSON, then let's clean up any multi-line content within its data.
        if (!$class::SUPPORTS_MSON) {
            // Don't remove line breaks from a description annotation.
            if ($class !== '\Mill\Parser\Annotations\\DescriptionAnnotation') {
                $content = preg_replace(MSON::REGEX_CLEAN_MULTILINE, ' ', $content);
            }
        }

        /** @var Annotation $annotation */
        $annotation = (new $class($content, $this->class, $this->method, $version))->process();

        if (!empty($decorators)) {
            $decorators = explode(':', ltrim($decorators, ':'));
            foreach ($decorators as $decorator) {
                switch ($decorator) {
                    // Acceptable decorators
                    case 'private':
                    case 'public':
                        $annotation->setVisibility(($decorator === 'public') ? true : false);
                        break;

                    case 'deprecated':
                        $annotation->setDeprecated(true);
                        break;

                    case 'alias':
                        $annotation->setAliased(true);
                        break;

                    default:
                        /** @var string $method */
                        $method = $this->method;
                        throw UnsupportedDecoratorException::create(
                            $decorator,
                            $name,
                            $this->class,
                            $method
                        );
                }
            }
        }

        return $annotation;
    }

    /**
     * Get the class name of a given annotation.
     *
     * @param string $annotation
     * @return string
     */
    private function getAnnotationClass(string $annotation): string
    {
        // Not all filesystems support case-insensitive file loading, so we need to map multi-word annotations to the
        // properly capitalized class name.
        $annotation = strtolower($annotation);
        switch ($annotation) {
            case 'contenttype':
                $annotation = 'ContentType';
                break;

            case 'minversion':
                $annotation = 'MinVersion';
                break;

            case 'urisegment':
                $annotation = 'UriSegment';
                break;

            default:
                $annotation = ucfirst($annotation);
        }

        return '\Mill\Parser\Annotations\\' . $annotation . 'Annotation';
    }

    /**
     * Parse out annotations from a supplied docblock.
     *
     * @param string $docblock
     * @return Docblock
     */
    public static function getAnnotationsFromDocblock(string $docblock): Docblock
    {
        return new Docblock($docblock);
    }

    /**
     * @param null|string $method
     * @return self
     */
    public function setMethod(string $method = null): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Given an UnknownTag object, get back the Mill annotation name from it.
     *
     * @param UnknownTag $tag
     * @return string
     */
    protected function getAnnotationNameFromTag(UnknownTag $tag): string
    {
        $annotation = $tag->getTagName();
        return substr($annotation, 4);
    }
}
