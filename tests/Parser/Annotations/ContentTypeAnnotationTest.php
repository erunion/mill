<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\ContentTypeAnnotation;
use Mill\Parser\Version;

class ContentTypeAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param array $expected
     */
    public function testAnnotation(string $content, ?Version $version, array $expected): void
    {
        $annotation = new ContentTypeAnnotation($content, __CLASS__, __METHOD__, $version);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param array $expected
     */
    public function testHydrate(string $content, ?Version $version, array $expected): void
    {
        $annotation = ContentTypeAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ), $version);

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(ContentTypeAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsVendorTags());
        $this->assertFalse($annotation->requiresVisibilityDecorator());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['content_type'], $annotation->getContentType());
        $this->assertEmpty($annotation->getVendorTags());

        if ($expected['version']) {
            $this->assertInstanceOf(Version::class, $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }

        $this->assertEmpty($annotation->getAliases());
    }

    public function providerAnnotation(): array
    {
        return [
            'versioned' => [
                'content_type' => 'application/vendor.mime.type',
                'version' => new Version('1.1 - 1.2', __CLASS__, __METHOD__),
                'expected' => [
                    'content_type' => 'application/vendor.mime.type',
                    'version' => '1.1 - 1.2'
                ]
            ],
            '_complete' => [
                'content_type' => 'application/json',
                'version' => null,
                'expected' => [
                    'content_type' => 'application/json',
                    'version' => false
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-content-type' => [
                'annotation' => ContentTypeAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'content_type',
                    'getAnnotation' => 'contenttype',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ]
        ];
    }
}
