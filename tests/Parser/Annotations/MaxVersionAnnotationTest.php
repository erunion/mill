<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\AbsoluteVersionException;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Parser\Annotations\MaxVersionAnnotation;

class MaxVersionAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $annotation = new MaxVersionAnnotation($content, __CLASS__, __METHOD__);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(MaxVersionAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsVendorTags());
        $this->assertFalse($annotation->requiresVisibilityDecorator());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertEmpty($annotation->getVendorTags());
        $this->assertFalse($annotation->getVersion());
    }

    public function providerAnnotation(): array
    {
        return [
            '_complete' => [
                'content' => '1.2',
                'expected' => [
                    'maximum_version' => '1.2'
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'does-not-have-an-absolute-version' => [
                'annotation' => MaxVersionAnnotation::class,
                'content' => '~1.2',
                'expected.exception' => AbsoluteVersionException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => '~1.2',
                    'getDocblock' => null,
                    'getValues' => []
                ]
            ],
            'missing-maximum-version' => [
                'annotation' => MaxVersionAnnotation::class,
                'content' => '',
                'expected.exception' => UnrecognizedSchemaException::class,
                'expected.exception.asserts' => [
                    'getVersion' => '',
                    'getValidationMessage' => 'The supplied version, ``, has an unrecognized schema. Please consult ' .
                        'the versioning documentation.'
                ]
            ]
        ];
    }
}
