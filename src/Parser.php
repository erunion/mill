<?php
namespace Mill;

use Mill\Exceptions\Resource\UnsupportedDecoratorException;
use Mill\Parser\Annotation;
use Mill\Parser\Version;
use ReflectionClass;

/**
 * Class for tokenizing a docblock on a given controller or method.
 *
 */
class Parser
{
    const ANNOTATION_REGEX = '/^\s?@api-(\w+)((:\w+)+)?(\n|\s*([^\n]*))/m';

    /**
     * The current controller that we're going to be parsing.
     *
     * @var string
     */
    protected $controller;

    /**
     * The current method that we're parsing. Used to give better error messaging.
     *
     * @var string
     */
    protected $method;

    /**
     * @param string $controller
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Get an array of HTTP (GET, POST, PUT, PATCH, DELETE) methods that are implemented on the current controller.
     *
     * @return array
     */
    public function getHttpMethods()
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        $reflection = new ReflectionClass($this->controller);

        $valid_methods = [];
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method)) {
                $valid_methods[] = $method;
            }
        }

        return $valid_methods;
    }

    /**
     * Locate, and parse, the annotations for a controller or method.
     *
     * @param string|null $method_name
     * @return array An array containing all the found annotations.
     */
    public function getAnnotations($method_name = null)
    {
        if (!empty($method_name)) {
            $this->method = $method_name;
        }

        $reader = Container::getAnnotationReader();
        $comments = $reader($this->controller, $method_name);

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
    protected function parseDocblock($docblock, $parse_description = true)
    {
        $original_docblock = $docblock;
        $annotations = [];
        $matches = null;

        $docblock = self::cleanDocblock($docblock);

        $matches = self::getAnnotationsFromDocblock($docblock);
        if (is_array($matches)) {
            $docblock = preg_replace('/^\s?@(\w+)\s*([^\n]*)/m', '', $docblock);
            $annotations = $this->parseAnnotations($matches, $original_docblock);
        }

        // Only parse out a `description` annotation if we need to (like in the instance of not parsing a
        // representation).
        if (!$parse_description) {
            return $annotations;
        }

        // If there's anything left over, clean it up, and store it as the `description` annotation.
        //
        // We're doing this instead of having a more structured `@description` annotation because matching multiple
        // lines within an existing multi-line regex is incredibly difficult.
        $description = str_replace("\t", '  ', $docblock);

        // Smush the whole docblock to the left edge.
        $min_indent = 80;
        $indent = 0;
        foreach (array_filter(explode("\n", $description)) as $line) {
            for ($ii = 0; $ii < strlen($line); $ii++) {
                if ($line[$ii] != ' ') {
                    break;
                }

                $indent++;
            }

            $min_indent = min($indent, $min_indent);
        }

        $description = preg_replace('/^' . str_repeat(' ', $min_indent) . '/m', '', $description);
        $description = rtrim($description);

        // Trim any empty lines off the front, but leave the indent level if there is one.
        $description = preg_replace('/^\s*\n/', '', $description);

        if (!empty($description)) {
            $annotations['description'][] = $this->buildAnnotationData('description', null, $description);
        }

        return $annotations;
    }

    /**
     * Parse a group of our custom annotations.
     *
     * @param array $matches
     * @param string $original_docblock
     * @return array
     */
    protected function parseAnnotations($matches, $original_docblock)
    {
        $annotations = [];
        $version = null;
        foreach ($matches as $match) {
            list($_, $annotation, $decorators, $_last_decorator, $data) = $match;

            if ($annotation !== 'version' && empty($annotations[$annotation])) {
                $annotations[$annotation] = [];
            }

            $data = trim($data);
            switch ($annotation) {
                // Handle the `@api-version` annotation block.
                case 'version':
                    $version = new Version($data, $this->controller, $this->method);
                    break;

                // Parse all other annotations.
                default:
                    $annotations[$annotation][] = $this->buildAnnotationData(
                        $annotation,
                        $decorators,
                        $data,
                        $version
                    );
            }
        }

        return $annotations;
    }

    /**
     * Build up an array of annotation data.
     *
     * @param string $name
     * @param string|null $decorators
     * @param string $data
     * @param Version|null $version
     * @return Annotation
     * @throws UnsupportedDecoratorException If an unsupported decorator is found on an annotation.
     */
    private function buildAnnotationData($name, $decorators, $data, Version $version = null)
    {
        $class = $this->getAnnotationClass($name);

        /** @var Annotation $annotation */
        $annotation = new $class($data, $this->controller, $this->method, $version);

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
                        throw UnsupportedDecoratorException::create(
                            $decorator,
                            $name,
                            $this->controller,
                            $this->method
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
    private function getAnnotationClass($annotation)
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
     * Clean a supplied docblock by removing comments and normalizing multi-line annotations.
     *
     * @param string $docblock
     * @return string
     */
    public static function cleanDocblock($docblock)
    {
        // Strip off comments.
        $docblock = trim($docblock);
        $docblock = preg_replace('@^/\*\*@', '', $docblock);
        $docblock = preg_replace('@\*/$@', '', $docblock);
        $docblock = preg_replace('@^\s*\*@m', '', $docblock);

        // Normalize multi-line annotations.
        $lines = explode("\n", $docblock);
        /** @var boolean|string $last */
        $last = false;
        foreach ($lines as $k => $line) {
            if (preg_match('/^\s?@\w/i', $line)) {
                $last = $k;
            } elseif (preg_match('/^\s*$/', $line)) {
                $last = false;
            } elseif ($last !== false) {
                $lines[$last] = rtrim($lines[$last]).' '.trim($line);
                unset($lines[$k]);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Parse out annotations from a supplied, cleaned, docblock.
     *
     * @param string $docblock
     * @return array|false
     */
    public static function getAnnotationsFromDocblock($docblock)
    {
        $has_annotations = preg_match_all(self::ANNOTATION_REGEX, $docblock, $matches, PREG_SET_ORDER);
        if (!$has_annotations) {
            return false;
        }

        return $matches;
    }
}
