<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\LabelAnnotation;

class LabelAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, array $expected)
    {
        $annotation = new LabelAnnotation($content, __CLASS__, __METHOD__);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertFalse($annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            '_complete' => [
                'content' => 'Update a movie.',
                'expected' => [
                    'label' => 'Update a movie.'
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
            'missing-label' => [
                'annotation' => '\Mill\Parser\Annotations\LabelAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'label',
                    'getAnnotation' => 'label',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ]
        ];
    }
}
