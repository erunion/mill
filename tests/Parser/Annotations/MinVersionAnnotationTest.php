<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\AbsoluteMinimumVersionException;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Parser\Annotations\MinVersionAnnotation;
use Mill\Parser\Reader\Docblock;

class MinVersionAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $docblock = new Docblock($content, __FILE__, 0, strlen($content));
        $annotation = new MinVersionAnnotation($this->application, $content, $docblock);
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
        $annotation = MinVersionAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }*/

    private function assertAnnotation(MinVersionAnnotation $annotation, array $expected): void
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
                'content' => '1.2',
                'expected' => [
                    'minimum_version' => '1.2'
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'does-not-have-an-absolute-version' => [
                'annotation' => MinVersionAnnotation::class,
                'content' => '~1.2',
                'expected.exception' => AbsoluteMinimumVersionException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => '~1.2',
                    //'getDocblock' => null,
                    //'getValues' => []
                ]
            ],
            'missing-minimum-version' => [
                'annotation' => MinVersionAnnotation::class,
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
