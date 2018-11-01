<?php
namespace Mill\Parser\Representation;

use Dflydev\DotAccessData\Data;
use Mill\Application;
use Mill\Contracts\Arrayable;
use Mill\Exceptions\Annotations\MultipleAnnotationsException;
use Mill\Exceptions\Annotations\RequiredAnnotationException;
use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Parser;

class Documentation implements Arrayable
{
    /** @var string Name of the representation class that we're going to be parsing for documentation. */
    protected $class;

    /** @var string Name of the representation class method that we're going to be parsing for documentation. */
    protected $method;

    /** @var Application */
    protected $application;

    /** @var string Short description/label/title of the representation. */
    protected $label;

    /**
     * Fuller description of what this representation handles. This should normally consist of Markdown.
     *
     * @var null|string
     */
    protected $description = null;

    /** @var array Array of parsed field annotations that exist on this representation. */
    protected $representation = [];

    /**
     * @param string $class
     * @param string $method
     * @param Application $application
     */
    public function __construct(string $class, string $method, Application $application)
    {
        $this->class = $class;
        $this->method = $method;
        $this->application = $application;
    }

    /**
     * Parse the instance controller and method into actionable annotations and documentation.
     *
     * @return Documentation
     * @throws MultipleAnnotationsException
     * @throws NoAnnotationsException
     * @throws RequiredAnnotationException
     * @throws \Mill\Exceptions\MethodNotSuppliedException
     * @throws \Mill\Exceptions\Representation\DuplicateFieldException
     * @throws \Mill\Exceptions\Resource\UnsupportedDecoratorException
     */
    public function parse(): self
    {
        $annotations = (new Parser($this->class, $this->application))->setMethod($this->method)->getAnnotations();

        $this->representation = (new RepresentationParser($this->class, $this->application))->getAnnotations($this->method);

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
     * Filter down, and return, all annotations on this representation to a specific version.
     *
     * @param string $version
     * @return array
     */
    public function filterRepresentationForVersion(string $version): array
    {
        /** @var Parser\Annotation $annotation */
        foreach ($this->representation as $name => $annotation) {
            // If this annotation has a set version, but that version doesn't match what we're looking for, filter it
            // out.
            $annotation_version = $annotation->getVersion();
            if ($annotation_version) {
                if (!$annotation_version->matches($version)) {
                    unset($this->representation[$name]);
                }
            }
        }

        return $this->representation;
    }

    /**
     * Filter down, and return, all annotations on this representation that match a specific vendor tag.
     *
     * @psalm-suppress RedundantCondition
     * @param array|null $only_vendor_tags
     * @return array
     */
    public function filterAnnotationsForVisibility(?array $only_vendor_tags): array
    {
        if (is_null($only_vendor_tags)) {
            return $this->representation;
        }

        /** @var Parser\Annotation $annotation */
        foreach ($this->representation as $name => $annotation) {
            // If this annotation has vendor tags, but those vendor tags aren't in the set of vendor tags we're
            // compiling documentation for, filter it out.
            $vendor_tags = $annotation->getVendorTags();
            if (!empty($vendor_tags)) {
                // If we don't even have vendor tags to look for, then filter this annotation out completely.
                if (!is_null($only_vendor_tags) && empty($only_vendor_tags)) {
                    unset($this->representation[$name]);
                    continue;
                }

                $all_found = true;

                /** @var Parser\Annotations\VendorTagAnnotation $vendor_tag */
                foreach ($vendor_tags as $vendor_tag) {
                    $vendor_tag = $vendor_tag->getVendorTag();

                    if (!is_null($only_vendor_tags) && !in_array($vendor_tag, $only_vendor_tags)) {
                        $all_found = false;
                    }
                }

                if (!$all_found) {
                    unset($this->representation[$name]);
                    continue;
                }

                // Vendor tags requirements override individual annotation visibility.
                continue;
            }
        }

        return $this->representation;
    }

    /**
     * Get the representation class that we're parsing.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get the representation class method that we're parsing.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the label of this representation.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Pull the raw content of this representation. This will be an array of Annotation objects.
     *
     * @return array
     */
    public function getRawContent(): array
    {
        return $this->representation;
    }

    /**
     * Pull the content of this representation. This will be an array of `toArray`'d Annotation objects.
     *
     * @return array
     */
    public function getContent(): array
    {
        return $this->toArray()['content'];
    }

    /**
     * Convert the parsed representation documentation content dot notation field names into an exploded array.
     *
     * @return array
     */
    public function getExplodedContentDotNotation(): array
    {
        $content = new Data;

        $arr = $this->toArray();
        foreach ($arr['content'] as $field => $data) {
            $content->set($field, [
                Application::DOT_NOTATION_ANNOTATION_DATA_KEY => $data
            ]);
        }

        return $content->export();
    }

    /**
     * {{@inheritdoc}}
     */
    public function toArray(): array
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

        ksort($data['content']);

        return $data;
    }
}
