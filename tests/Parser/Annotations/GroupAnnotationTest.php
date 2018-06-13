<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\GroupAnnotation;

class GroupAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $annotation = new GroupAnnotation($content, __CLASS__, __METHOD__);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(GroupAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsVendorTags());
        $this->assertFalse($annotation->requiresVisibilityDecorator());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertEmpty($annotation->getVendorTags());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    public function providerAnnotation(): array
    {
        return [
            '_complete' => [
                'content' => 'Movies\Coming Soon',
                'expected' => [
                    'group' => 'Movies\Coming Soon'
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-group' => [
                'annotation' => GroupAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'group',
                    'getAnnotation' => 'group',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ]
        ];
    }
}
