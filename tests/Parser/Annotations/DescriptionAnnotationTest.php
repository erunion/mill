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
     * @return void
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $annotation = (new DescriptionAnnotation($content, __CLASS__, __METHOD__))->process();
        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testHydrate(string $content, array $expected): void
    {
        $annotation = DescriptionAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @param DescriptionAnnotation $annotation
     * @param array $expected
     * @return void
     */
    private function assertAnnotation(DescriptionAnnotation $annotation, array $expected): void
    {
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

    /**
     * @return array
     */
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
