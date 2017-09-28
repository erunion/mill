<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\LabelAnnotation;

class LabelAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $annotation = (new LabelAnnotation($content, __CLASS__, __METHOD__))->process();
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
        $annotation = LabelAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @param LabelAnnotation $annotation
     * @param array $expected
     * @return void
     */
    private function assertAnnotation(LabelAnnotation $annotation, array $expected): void
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
                'content' => 'Update a movie.',
                'expected' => [
                    'label' => 'Update a movie.'
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
            'missing-label' => [
                'annotation' => LabelAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'label',
                    'getAnnotation' => 'label',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ]
        ];
    }
}
