<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-method` annotation.
 *
 */
class MethodAnnotation extends Annotation
{
    /** @var string */
    protected $method;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'method'
    ];

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        // @todo reject bad http methods

        return [
            'method' => $this->content
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->method = $this->required('method');
    }

    /**
     * {@inheritdoc}
     */
    /*public static function hydrate(array $data = [], Version $version = null): self\
    {
        // @var MethodAnnotation $annotation
        $annotation = parent::hydrate($data, $version);
        $annotation->setMethod($data['method']);

        return $annotation;
    }*/

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return self
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }
}
