<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\DescriptionAnnotation;

class DescriptionAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $annotation = new DescriptionAnnotation($content, __CLASS__, __METHOD__);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(DescriptionAnnotation $annotation, array $expected): void
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
                'content' => 'This is a description.',
                'expected' => [
                    'description' => 'This is a description.'
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-description' => [
                'annotation' => DescriptionAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'description',
                    'getAnnotation' => 'description',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ]
        ];
    }
}
