<?php
namespace Mill\Parser\Resource;

use Mill\Application;
use Mill\Contracts\Arrayable;
use Mill\Exceptions\Annotations\MultipleAnnotationsException;
use Mill\Exceptions\Annotations\RequiredAnnotationException;
use Mill\Exceptions\MethodNotImplementedException;
use Mill\Parser;

class Documentation implements Arrayable
{
    /**
     * @psalm-var class-string
     * @var string Class that we're parsing for documentation.
     */
    protected $class;

    /** @var array Array of parsed method documentation for the current resource. */
    protected $methods = [];

    /** @var Application */
    protected $application;

    /** @var Parser Current Parser instance. */
    protected $parser;

    /**
     * @psalm-param class-string $class
     * @param string $class
     * @param Application $application
     */
    public function __construct(string $class, Application $application)
    {
        $this->class = $class;
        $this->application = $application;
        $this->parser = new Parser($this->class, $application);
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
            $this->methods[$method] = (new Action\Documentation($this->class, $method, $this->application))->parse();
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
            'methods' => []
        ];

        /** @var Action\Documentation $object */
        foreach ($this->methods as $method => $object) {
            $data['methods'][$method] = $object->toArray();
        }

        return $data;
    }
}
