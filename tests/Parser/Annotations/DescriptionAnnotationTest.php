<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\DescriptionAnnotation;

class DescriptionAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider annotationProvider
     */
    public function testAnnotation($description, $expected)
    {
        $annotation = new DescriptionAnnotation($description, __CLASS__, __METHOD__);

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
                'description' => 'This is a description.',
                'expected' => [
                    'description' => 'This is a description.'
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
            'missing-description' => [
                'annotation' => '\Mill\Parser\Annotations\DescriptionAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`description`/'
                ]
            ]
        ];
    }
}
