<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-contentType` annotation.
 *
 */
class ContentTypeAnnotation extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_DEPRECATION = false;
    const SUPPORTS_VERSIONING = true;

    /**
     * Content type.
     *
     * @var string
     */
    protected $content_type;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'content_type'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     */
    protected function parser()
    {
        return [
            'content_type' => $this->docblock
        ];
    }

    /**
     * Interpret the parsed annotation data and set local variables to build the annotation.
     *
     * To facilitate better error messaging, the order in which items are interpreted here should be match the schema
     * of the annotation.
     *
     * @return void
     */
    protected function interpreter()
    {
        $this->content_type = $this->required('content_type');
    }

    /**
     * With an array of data that was output from an Annotation, via `toArray()`, hydrate a new Annotation object.
     *
     * @param array $data
     * @param Version|null $version
     * @return self
     */
    public static function hydrate(array $data = [], Version $version = null): self
    {
        /** @var ContentTypeAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setContentType($data['content_type']);

        return $annotation;
    }

    /**
     * Get the content type.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * @param string $content_type
     * @return self
     */
    public function setContentType(string $content_type): self
    {
        $this->content_type = $content_type;
        return $this;
    }
}
