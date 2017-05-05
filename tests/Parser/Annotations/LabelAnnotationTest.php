<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\LabelAnnotation;

class LabelAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($label, $expected)
    {
        $annotation = new LabelAnnotation($label, __CLASS__, __METHOD__);

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
                'label' => 'Update a movie.',
                'expected' => [
                    'label' => 'Update a movie.'
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
            'missing-label' => [
                'annotation' => '\Mill\Parser\Annotations\LabelAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
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
