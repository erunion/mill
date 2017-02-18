<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ContentTypeAnnotation;

class ContentTypeAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($capability, $expected)
    {
        $annotation = new ContentTypeAnnotation($capability, __CLASS__, __METHOD__);

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
                'content_type' => 'application/json',
                'expected' => [
                    'content_type' => 'application/json'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidAnnotations()
    {
        return [
            'missing-content-type' => [
                'annotation' => '\Mill\Parser\Annotations\ContentTypeAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`content_type`/'
                ]
            ]
        ];
    }
}
