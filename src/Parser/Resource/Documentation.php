<?php
namespace Mill\Parser\Resource;

use Mill\Contracts\Arrayable;
use Mill\Exceptions\Annotations\MultipleAnnotationsException;
use Mill\Exceptions\Annotations\RequiredAnnotationException;
use Mill\Exceptions\MethodNotImplementedException;
use Mill\Parser;

class Documentation implements Arrayable
{
    /** @var string Class that we're parsing for documentation. */
    protected $class;

    /** @var array Array of parsed method documentation for the current resource. */
    protected $methods = [];

    /** @var string Short description/label/title of the resource. */
    protected $label;

    /** @var null|string Fuller description of what this resource handles. This should normally consist of Markdown. */
    protected $description = null;

    /** @var Parser Current Parser instance. */
    protected $parser;

    /**
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $this->parser = new Parser($this->class);
    }

    /**
     * Parse the instance class into actionable annotations and documentation.
     *
     * @return Documentation
     * @throws MultipleAnnotationsException
     * @throws RequiredAnnotationException
     * @throws \Mill\Exceptions\Resource\UnsupportedDecoratorException
     */
    public function parse(): self
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
     * @throws MultipleAnnotationsException
     * @throws RequiredAnnotationException
     * @throws \Mill\Exceptions\Resource\MissingVisibilityDecoratorException
     * @throws \Mill\Exceptions\Resource\NoAnnotationsException
     * @throws \Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException
     * @throws \Mill\Exceptions\Resource\TooManyAliasedPathsException
     * @throws \Mill\Exceptions\Resource\UnsupportedDecoratorException
     * @throws \ReflectionException
     */
    public function parseMethods(): self
    {
        $this->getMethods();
        return $this;
    }

    /**
     * Return the parsed method documentation for HTTP Methods that are implemented on the current class.
     *
     * @return array
     * @throws MultipleAnnotationsException
     * @throws RequiredAnnotationException
     * @throws \Mill\Exceptions\Resource\MissingVisibilityDecoratorException
     * @throws \Mill\Exceptions\Resource\NoAnnotationsException
     * @throws \Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException
     * @throws \Mill\Exceptions\Resource\TooManyAliasedPathsException
     * @throws \Mill\Exceptions\Resource\UnsupportedDecoratorException
     * @throws \ReflectionException
     */
    public function getMethods(): array
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
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Pull a parsed MethodDocumentation object for a given method on this class.
     *
     * @param string $method
     * @return Action\Documentation
     * @throws MethodNotImplementedException
     * @throws MultipleAnnotationsException
     * @throws RequiredAnnotationException
     * @throws \Mill\Exceptions\Resource\MissingVisibilityDecoratorException
     * @throws \Mill\Exceptions\Resource\NoAnnotationsException
     * @throws \Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException
     * @throws \Mill\Exceptions\Resource\TooManyAliasedPathsException
     * @throws \Mill\Exceptions\Resource\UnsupportedDecoratorException
     * @throws \ReflectionException
     */
    public function getMethod(string $method): Action\Documentation
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
     * {{@inheritdoc}}
     */
    public function toArray(): array
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
