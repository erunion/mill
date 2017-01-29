<?php
namespace Mill\Parser\Resource;

use Mill\Exceptions\MethodNotImplementedException;
use Mill\Exceptions\MultipleAnnotationsException;
use Mill\Exceptions\RequiredAnnotationException;
use Mill\Parser;

/**
 * Class for parsing a docblock on a given class for resource documentation.
 *
 */
class Documentation
{
    /**
     * Name of the controller class that we're parsing for documentation.
     *
     * @var string
     */
    protected $controller;

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
     * @param string $controller
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
        $this->parser = new Parser($this->controller);
    }

    /**
     * Parse the instance controller into actionable annotations and documentation.
     *
     * @return Documentation
     * @throws RequiredAnnotationException If a required `@api-label` annotation is missing.
     * @throws MultipleAnnotationsException If multiple `@api-label` annotations were found.
     */
    public function parse()
    {
        $annotations = $this->parser->getAnnotations();

        if (!isset($annotations['label'])) {
            throw RequiredAnnotationException::create('label', $this->controller);
        } elseif (count($annotations['label']) > 1) {
            throw MultipleAnnotationsException::create('label', $this->controller);
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
     * Example: `$documentation = (new Documentation($controller))->parseMethods()->toArray();`
     *
     * @return Documentation
     */
    public function parseMethods()
    {
        $this->getMethods();
        return $this;
    }

    /**
     * Return the parsed method documentation for HTTP Methods that are implemented on the current controller.
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
            $this->methods[$method] = (new Action\Documentation($this->controller, $method))->parse();
        }

        return $this->methods;
    }

    /**
     * Get the class name of the controller we're parsing for documentation.
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Pull a parsed MethodDocumentation object for a given method on this controller.
     *
     * @param string $method
     * @return Action\Documentation
     * @throws MethodNotImplementedException If the instance controller does not implement the supplied method.
     */
    public function getMethod($method)
    {
        if (empty($this->methods)) {
            $this->getMethods();
        }

        if (empty($this->methods[$method])) {
            throw MethodNotImplementedException::create($this->getController(), $method);
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
            'controller' => $this->controller,
            'label' => $this->label,
            'description' => $this->description,
            'methods' => []
        ];

        /** @var Action\Documentation $object */
        foreach ($this->methods as $method => $object) {
            $data['methods'][$method] = $object->toArray();
        }

        return $data;
    }
}
