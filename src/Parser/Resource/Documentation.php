<?php
namespace Mill\Parser\Resource;

use Mill\Exceptions\Annotations\MultipleAnnotationsException;
use Mill\Exceptions\Annotations\RequiredAnnotationException;
use Mill\Exceptions\MethodNotImplementedException;
use Mill\Parser;

/**
 * Class for parsing a docblock on a given class for resource documentation.
 *
 */
class Documentation
{
    /**
     * Class that we're parsing for documentation.
     *
     * @var string
     */
    protected $class;

    /**
     * Array of parsed method documentation for the current resource.
     *
     * @var array
     */
    protected $methods = [];

    /**
     * Short description/label/title of the resource.
     *
     * @var string
     */
    protected $label;

    /**
     * Fuller description of what this resource handles. This should normally consist of Markdown.
     *
     * @var string|null
     */
    protected $description = null;

    /**
     * Current Parser instance.
     *
     * @var Parser
     */
    protected $parser;

    /**
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;
        $this->parser = new Parser($this->class);
    }

    /**
     * Parse the instance class into actionable annotations and documentation.
     *
     * @return Documentation
     * @throws RequiredAnnotationException If a required `@api-label` annotation is missing.
     * @throws MultipleAnnotationsException If multiple `@api-label` annotations were found.
     */
    public function parse()
    {
        $annotations = $this->parser->getAnnotations();

        if (!isset($annotations['label'])) {
            throw RequiredAnnotationException::create('label', $this->class);
        } elseif (count($annotations['label']) > 1) {
            throw MultipleAnnotationsException::create('label', $this->class);
        }

        /** @var \Mill\Parser\Annotations\LabelAnnotation $annotation */
        $annotation = reset($annotations['label']);
        $this->label = $annotation->getLabel();

        if (!empty($annotations['description'])) {
            /** @var \Mill\Parser\Annotations\DescriptionAnnotation $annotation */
            $annotation = reset($annotations['description']);
            $this->description = $annotation->getDescription();
        }

        return $this;
    }

    /**
     * This is a chaining accessory to help you do one-liner instances of this class.
     *
     * Example: `$documentation = (new Documentation($class))->parseMethods()->toArray();`
     *
     * @return Documentation
     */
    public function parseMethods()
    {
        $this->getMethods();
        return $this;
    }

    /**
     * Return the parsed method documentation for HTTP Methods that are implemented on the current class.
     *
     * @return array
     */
    public function getMethods()
    {
        if (!empty($this->methods)) {
            return $this->methods;
        }

        $this->methods = array_flip($this->parser->getHttpMethods());
        foreach ($this->methods as $method => $val) {
            $this->methods[$method] = (new Action\Documentation($this->class, $method))->parse();
        }

        return $this->methods;
    }

    /**
     * Get the class name of the class we're parsing for documentation.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Pull a parsed MethodDocumentation object for a given method on this class.
     *
     * @param string $method
     * @return Action\Documentation
     * @throws MethodNotImplementedException If the instance class does not implement the supplied method.
     */
    public function getMethod($method)
    {
        if (empty($this->methods)) {
            $this->getMethods();
        }

        if (empty($this->methods[$method])) {
            throw MethodNotImplementedException::create($this->getClass(), $method);
        }

        return $this->methods[$method];
    }

    /**
     * Convert the parsed resource documentation into an array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [
            'class' => $this->class,
            'description' => $this->description,
            'label' => $this->label,
            'methods' => []
        ];

        /** @var Action\Documentation $object */
        foreach ($this->methods as $method => $object) {
            $data['methods'][$method] = $object->toArray();
        }

        return $data;
    }
}
