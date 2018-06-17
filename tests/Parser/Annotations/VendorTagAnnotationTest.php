<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\InvalidVendorTagSuppliedException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\VendorTagAnnotation;

class VendorTagAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $annotation = new VendorTagAnnotation($content, __CLASS__, __METHOD__);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(VendorTagAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsVendorTags());
        $this->assertFalse($annotation->requiresVisibilityDecorator());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['vendor_tag'], $annotation->getVendorTag());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    public function providerAnnotation(): array
    {
        return [
            '_complete' => [
                'content' => 'tag:BUY_TICKETS',
                'expected' => [
                    'vendor_tag' => 'tag:BUY_TICKETS'
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-tag' => [
                'annotation' => VendorTagAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'vendor_tag',
                    'getAnnotation' => 'vendortag',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'tag-was-not-configured' => [
                'annotation' => VendorTagAnnotation::class,
                'content' => 'tag:notConfigured',
                'expected.exception' => InvalidVendorTagSuppliedException::class,
                'expected.exception.asserts' => [
                    'getVendorTag' => 'tag:notConfigured'
                ]
            ]
        ];
    }
}
