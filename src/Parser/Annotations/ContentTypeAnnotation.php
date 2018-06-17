<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;

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
     * @return string
     */
    public function getContentType(): string
    {
        return $this->content_type;
    }

    /**
     * @param string $content_type
     * @return ContentTypeAnnotation
     */
    public function setContentType(string $content_type): self
    {
        $this->content_type = $content_type;
        return $this;
    }
}
