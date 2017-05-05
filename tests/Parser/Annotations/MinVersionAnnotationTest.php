<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\MinVersionAnnotation;

class MinVersionAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($version, $expected)
    {
        $annotation = new MinVersionAnnotation($version, __CLASS__, __METHOD__);

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
                'version' => '1.2',
                'expected' => [
                    'minimum_version' => '1.2'
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
            'does-not-have-an-absolute-version' => [
                'annotation' => '\Mill\Parser\Annotations\MinVersionAnnotation',
                'docblock' => '~1.2',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\AbsoluteMinimumVersionException',
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => '~1.2',
                    'getDocblock' => null,
                    'getValues' => []
                ]
            ],
            'missing-minimum-version' => [
                'annotation' => '\Mill\Parser\Annotations\MinVersionAnnotation',
                'docblock' => '',
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
