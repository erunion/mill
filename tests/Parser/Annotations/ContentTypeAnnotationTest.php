<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ContentTypeAnnotation;
use Mill\Parser\Version;

class ContentTypeAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, $version, array $expected)
    {
        $annotation = new ContentTypeAnnotation($content, __CLASS__, __METHOD__, $version);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($content, $annotation->getContentType());
        $this->assertFalse($annotation->getCapability());

        if ($expected['version']) {
            $this->assertInstanceOf('Mill\Parser\Version', $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }
    }

    /**
     * @return array
     */
    public function providerAnnotation()
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

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidContent()
    {
        return [
            'missing-content-type' => [
                'annotation' => '\Mill\Parser\Annotations\ContentTypeAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
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
