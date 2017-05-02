<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ContentTypeAnnotation;

class ContentTypeAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, array $expected)
    {
        $annotation = new ContentTypeAnnotation($content, __CLASS__, __METHOD__);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertFalse($annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            '_complete' => [
                'content' => 'application/json',
                'expected' => [
                    'content_type' => 'application/json'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidContent()
    {
        return [
            'missing-content-type' => [
                'annotation' => '\Mill\Parser\Annotations\ContentTypeAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'content_type',
                    'getAnnotation' => 'contenttype',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ]
        ];
    }
}
