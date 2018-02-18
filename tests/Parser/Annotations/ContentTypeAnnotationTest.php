<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\ContentTypeAnnotation;
use Mill\Parser\Reader\Docblock;
use Mill\Parser\Version;

class ContentTypeAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param null|string $version
     * @param array $expected
     */
    public function testAnnotation(string $content, ?string $version, array $expected): void
    {
        if ($version) {
            $version = new Version(
                $this->application,
                $version,
                new Docblock($content, __FILE__, 0, strlen($content))
            );
        }

        $docblock = new Docblock($content, __FILE__, 0, strlen($content));
        $annotation = new ContentTypeAnnotation($this->application, $content, $docblock, $version);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @ddataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param array $expected
     */
    /*public function testHydrate(string $content, ?Version $version, array $expected): void
    {
        $annotation = ContentTypeAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ), $version);

        $this->assertAnnotation($annotation, $expected);
    }*/

    private function assertAnnotation(ContentTypeAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['content_type'], $annotation->getContentType());
        $this->assertFalse($annotation->getCapability());

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
                'version_constraint' => '1.1 - 1.2',
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
                    //'getDocblock' => '',
                    //'getValues' => []
                ]
            ]
        ];
    }
}
