<?php
namespace Mill;

use gossi\docblock\tags\UnknownTag;
use Mill\Exceptions\Resource\UnsupportedDecoratorException;
use Mill\Parser\Annotation;
use Mill\Parser\MSON;
use Mill\Parser\Reader\Docblock;
use Mill\Parser\Version;

/**
 * Class for tokenizing a docblock on a given class or method.
 *
 */
class Parser
{
    const REGEX_DECORATOR = '/^(?P<decorator>(:\w+)+)?/u';

    /** @var Application */
    protected $application;

    /** @var Parser\Reader */
    protected $reader;

    /**
     * The current file that we're going to be parsing.
     *
     * @var string
     */
    protected $file;

    /**
     * @param Application $application
     * @param string $file
     */
    public function __construct(Application $application, string $file)
    {
        $this->application = $application;
        $this->file = $file;
        $this->reader = Container::getAnnotationReader();
    }

    /**
     * Get an array of HTTP (GET, POST, PUT, PATCH, DELETE) methods that are implemented on the current class.
     *
     * @return array
     */
    /*public function getHttpMethods()
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
    }*/

    /**
     * Quickly scan through the docblocks of the current file for a specific annotation. This is used to preload
     * assets before doing a full scan. Without doing this, representation maps would not be able to be validated
     * fully when doing a full scan.
     *
     * @param string $name
     * @return Annotation
     * @throws \Exception
     */
    public function getAnnotation(string $name): Annotation
    {
        $docblocks = $this->getDocblocks();
        foreach ($docblocks as $docblock) {
            $parser = $docblock->getAnnotations();
            $tags = $parser->getTags();
            if (!empty($tags)) {
                /** @var UnknownTag $tag */
                foreach ($tags as $tag) {
                    if ($tag->getTagName() !== 'api-' . $name) {
                        continue;
                    }

                    $annotation = $this->parseAnnotations([$tag], $docblock);
                    return array_shift($annotation[$name]);
                }
            }
        }

        throw new \Exception(sprintf(
            'An `@api-%s` annotation was not found within `%s`.',
            $name,
            $this->file
        ));
    }

    /**
     * Locate, and parse, the annotations on a file.
     *
     * @return array
     */
    public function getAnnotations(): array
    {
        $docblocks = $this->getDocblocks();

        $annotations = [];
        foreach ($docblocks as $docblock) {
            $annotations[] = $this->parseDocblock($docblock);
        }

        return $annotations;

/*print_r([
    'annotations' => count($annotations)
]);exit;*/

        //return $this->parseDocblock($comments);
    }

    /**
     * Parse a docblock comment into its parts.
     *
     * @link https://github.com/facebook/libphutil/blob/master/src/parser/docblock/PhutilDocblockParser.php
     * @param Docblock $docblock
     * @param boolean $parse_description If we want to parse out an unstructured `description` annotation.
     * @return array
     */
    protected function parseDocblock(Docblock $docblock, bool $parse_description = true): array
    {
        $annotations = [];
        //$matches = null;

        $parser = $docblock->getAnnotations();
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

            $annotations = $this->parseAnnotations($annotation_tags, $docblock);
        }

        // Only parse out a `description` annotation if we need to (like in the instance of not parsing a
        // representation).
        if ($parse_description) {
            // Reconstruct the description as the developer wrote it.
            $description = implode("\n\n", array_filter([
                $parser->getShortDescription(),
                $parser->getLongDescription()
            ]));

            if (!empty($description)) {
                $annotations['description'][] = $this->buildAnnotation($docblock, 'description', null, $description);
            }
        }

        return $annotations;
    }

    /**
     * Parse a group of our custom annotations.
     *
     * @param array $tags
     * @param Docblock $docblock
     * @return array
     */
    protected function parseAnnotations(array $tags, Docblock $docblock): array
    {
        $annotations = [];
        $version = null;

        /** @var UnknownTag $tag */
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
                    $version = new Version($this->application, $content, $docblock);
                    break;

                case 'see':
                    // @todo rebuild `@api-see support
                    break;

                // Parse all other annotations.
                default:
                    $annotations[$annotation][] = $this->buildAnnotation(
                        $docblock,
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
    /*public function hydrateAnnotation(string $name, string $class, string $method, array $data = []): Annotation
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
    }*/

    /**
     * Build up an array of annotation data.
     *
     * @param Docblock $docblock
     * @param string $name
     * @param null|string $decorators
     * @param string $content
     * @param null|Version $version
     * @return Annotation
     * @throws UnsupportedDecoratorException If an unsupported decorator is found on an annotation.
     */
    private function buildAnnotation(
        Docblock $docblock,
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
        $annotation = (new $class($this->application, $content, $docblock, $version))->process();

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
                        $this->application->trigger(
                            UnsupportedDecoratorException::create($decorator, $name, $docblock)
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

    protected function getDocblocks(): array
    {
        $docblocks = $this->reader->getAnnotations($this->file);
        if (empty($docblocks)) {
            return [];
        }

        return $docblocks;
    }
}
