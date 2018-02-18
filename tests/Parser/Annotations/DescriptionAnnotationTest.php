<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\DescriptionAnnotation;
use Mill\Parser\Reader\Docblock;

class DescriptionAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $docblock = new Docblock($content, __FILE__, 0, strlen($content));
        $annotation = new DescriptionAnnotation($this->application, $content, $docblock);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @ddataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    /*public function testHydrate(string $content, array $expected): void
    {
        $annotation = DescriptionAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }*/

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
                    //'getDocblock' => '',
                    //'getValues' => []
                ]
            ]
        ];
    }
}
