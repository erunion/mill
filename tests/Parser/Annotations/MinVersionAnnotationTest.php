<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\MinVersionAnnotation;

class MinVersionAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, array $expected)
    {
        $annotation = new MinVersionAnnotation($content, __CLASS__, __METHOD__);

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
                'content' => '1.2',
                'expected' => [
                    'minimum_version' => '1.2'
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
            'does-not-have-an-absolute-version' => [
                'annotation' => '\Mill\Parser\Annotations\MinVersionAnnotation',
                'content' => '~1.2',
                'expected.exception' => '\Mill\Exceptions\Annotations\AbsoluteMinimumVersionException',
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => '~1.2',
                    'getDocblock' => null,
                    'getValues' => []
                ]
            ],
            'missing-minimum-version' => [
                'annotation' => '\Mill\Parser\Annotations\MinVersionAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Version\UnrecognizedSchemaException',
                'expected.exception.asserts' => [
                    'getVersion' => '',
                    'getValidationMessage' => 'The supplied version, ``, has an unrecognized schema. Please consult ' .
                        'the versioning documentation.'
                ]
            ]
        ];
    }
}
