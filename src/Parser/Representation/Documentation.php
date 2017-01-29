<?php
namespace Mill\Parser\Representation;

use Dflydev\DotAccessData\Data;
use Mill\Exceptions\MultipleAnnotationsException;
use Mill\Exceptions\RequiredAnnotationException;
use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Parser;

/**
 * Class for parsing a docblock on a given representation class and method for documentation.
 *
 */
class Documentation
{
    /**
     * Name of the representation class that we're going to be parsing for documentation.
     *
     * @var string
     */
    protected $class;

    /**
     * Name of the representation class method that we're going to be parsing for documentation.
     *
     * @var string
     */
    protected $method;

    /**
     * Short description/label/title of the representation.
     *
     * @var string
     */
    protected $label;

    /**
     * Fuller description of what this representation handles. This should normally consist of Markdown.
     *
     * @var string|null
     */
    protected $description = null;

    /**
     * Array of parsed field annotations that exist on this representation.
     *
     * @var array
     */
    protected $representation = [];

    /**
     * @param string $class
     * @param string $method
     */
    public function __construct($class, $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Parse the instance controller and method into actionable annotations and documentation.
     *
     * @return Documentation
     * @throws NoAnnotationsException If no annotations were found on the class.
     * @throws NoAnnotationsException If no annotations were found on the method.
     * @throws RequiredAnnotationException If a required `@api-label` annotation is missing.
     * @throws MultipleAnnotationsException If multiple `@api-label` annotations were found.
     */
    public function parse()
    {
        $annotations = (new Parser($this->class))->getAnnotations();

        $this->representation = (new RepresentationParser($this->class))->getAnnotations($this->method);

        if (empty($annotations)) {
            throw NoAnnotationsException::create($this->class, null);
        } elseif (empty($this->representation)) {
            throw NoAnnotationsException::create($this->class, $this->method);
        }

        // Parse out the `@api-label` annotation.
        if (!isset($annotations['label'])) {
            throw RequiredAnnotationException::create('label', $this->class, $this->method);
        } elseif (count($annotations['label']) > 1) {
            throw MultipleAnnotationsException::create('label', $this->class, $this->method);
        } else {
            /** @var \Mill\Parser\Annotations\LabelAnnotation $annotation */
            $annotation = reset($annotations['label']);
            $this->label = $annotation->getLabel();
        }

        // Parse out the description block, if it's present.
        if (!empty($annotations['description'])) {
            /** @var \Mill\Parser\Annotations\DescriptionAnnotation $annotation */
            $annotation = reset($annotations['description']);
            $this->description = $annotation->getDescription();
        }

        return $this;
    }

    /**
     * Get the representation class that we're parsing.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get the representation class method that we're parsing.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Pull the content of this representation.
     *
     * @return array
     */
    public function getContent()
    {
        return $this->toArray()['content'];
    }

    /**
     * Convert the parsed representation documentation content dot notation field names into an exploded array.
     *
     * @return array
     */
    public function getExplodedContentDotNotation()
    {
        $content = new Data;

        $arr = $this->toArray();
        foreach ($arr['content'] as $field => $data) {
            $content->set($field, [
                '__FIELD_DATA__' => $data
            ]);
        }

        return $content->export();
    }

    /**
     * Convert the parsed representation method documentation into an array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [
            'label' => $this->label,
            'description' => $this->description,
            'content' => []
        ];

        foreach ($this->representation as $key => $annotation) {
            /** @var \Mill\Parser\Annotation $annotation */
            $data['content'][$key] = $annotation->toArray();
        }

        // Keep things tidy.
        ksort($data['content']);

        return $data;
    }
}
