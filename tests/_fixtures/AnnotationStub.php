<?php
namespace Mill\Tests\Fixtures;

use Mill\Parser\Annotation;

class AnnotationStub extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_VERSIONING = false;
    const SUPPORTS_DEPRECATION = false;

    /** @var null|string */
    protected $test = null;

    protected function parser(): array
    {
        return [
            'foo' => $this->content
        ];
    }

    protected function interpreter(): void
    {
        $this->test = $this->required('test');
    }

    public function toArray(): array
    {
        return [
            'test' => $this->test
        ];
    }
}
