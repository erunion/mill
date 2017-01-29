<?php
namespace Mill\Parser\Representation;

use ReflectionClass;
use Mill\Exceptions\MethodNotImplementedException;
use Mill\Exceptions\MethodNotSuppliedException;
use Mill\Exceptions\Representation\DuplicateFieldException;
use Mill\Parser;
use Mill\Parser\Annotations\FieldAnnotation;

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
            throw new MethodNotSuppliedException();
        }

        $this->method = $method_name;

        $reflection = new ReflectionClass($this->class);
        if (!$reflection->hasMethod($this->method)) {
            throw MethodNotImplementedException::create($this->controller, $this->method);
        }

        /** @var \ReflectionMethod $method */
        $method = $reflection->getMethod($this->method);
        $filename = $method->getFileName();

        // The start line is actually `- 1`, otherwise you wont get the function() block.
        $start_line = $method->getStartLine() - 1;
        $end_line = $method->getEndLine();
        $length = $end_line - $start_line;

        /** @var array $source */
        $source = file($filename);
        $code = implode('', array_slice($source, $start_line, $length));

        $annotations = $this->parse($code);

        // Keep things tidy.
        ksort($annotations);

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

        // Does this have any `@api-see` pointers?
        foreach ($matches as $k => $match) {
            list($_, $annotation, $decorator, $_last_decorator, $data) = $match;
            if ($annotation !== 'see') {
                continue;
            }

            $parts = explode(' ', trim($data));
            list($see_class, $see_method) = explode('::', array_shift($parts));
            $prefix = array_shift($parts);

            $parser = new self($see_class);
            $see_annotations = $parser->getAnnotations($see_method);

            // If this `@api-see` has a prefix to attach to found annotations, do so.
            if (!empty($prefix)) {
                /** @var FieldAnnotation $annotation */
                foreach ($see_annotations as $name => $annotation) {
                    $see_annotations[$prefix . '.' . $name] = $annotation->setFieldNamePrefix($prefix);
                    unset($see_annotations[$name]);
                }
            }

            $annotations += $see_annotations;
            unset($matches[$k]);
        }

        // If $matches is empty, then we only parsed `@api-see` annotations, so let's drop out.
        if (empty($matches)) {
            return $annotations;
        }

        /** @var \Mill\Parser\Annotations\FieldAnnotation $annotation */
        $annotation = new FieldAnnotation(
            $original_docblock,
            $this->class,
            $this->method,
            null,
            [
                'docblock_lines' => $matches
            ]
        );

        $annotations[$annotation->getFieldName()] = $annotation;

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
