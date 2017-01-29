<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\LabelAnnotation;

class LabelAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider annotationProvider
     */
    public function testAnnotation($label, $expected)
    {
        $annotation = new LabelAnnotation($label, __CLASS__, __METHOD__);

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
    public function annotationProvider()
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
    public function badAnnotationProvider()
    {
        return [
            'missing-label' => [
                'annotation' => '\Mill\Parser\Annotations\LabelAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`label`/'
                ]
            ]
        ];
    }
}
