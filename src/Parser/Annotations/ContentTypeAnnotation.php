<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-contenttype` annotation.
 *
 */
class ContentTypeAnnotation extends Annotation
{
    const SUPPORTS_VERSIONING = true;

    const ARRAYABLE = [
        'content_type'
    ];

    /** @var string */
    protected $content_type;

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        return [
            'content_type' => $this->docblock
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->content_type = $this->required('content_type');
    }

    /**
     * {@inheritdoc}
     */
    public static function hydrate(array $data = [], Version $version = null): self
    {
        /** @var ContentTypeAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setContentType($data['content_type']);

        return $annotation;
    }

    /**
     * @return string
     */
    public function getContentType(): string
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
