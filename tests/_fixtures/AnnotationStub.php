<?php
namespace Mill\Tests\Fixtures;

use Mill\Parser\Annotation;

class AnnotationStub extends Annotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_VERSIONING = false;
    const SUPPORTS_DEPRECATION = false;

    /**
     * @var null
     */
    protected $test = null;

    protected function parser()
    {
        return [
            'foo' => $this->docblock
        ];
    }

    protected function interpreter()
    {
        $this->test = $this->required('test');
    }

    public function toArray()
    {
        return [
            'test' => $this->test
        ];
    }
}
