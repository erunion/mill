<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\InvalidCapabilitySuppliedException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\CapabilityAnnotation;
use Mill\Parser\Reader\Docblock;

class CapabilityAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $docblock = new Docblock($content, __FILE__, 0, strlen($content));
        $annotation = new CapabilityAnnotation($this->application, $content, $docblock);
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
        $annotation = CapabilityAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }*/

    private function assertAnnotation(CapabilityAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['capability'], $annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    public function providerAnnotation(): array
    {
        return [
            '_complete' => [
                'content' => 'BUY_TICKETS',
                'expected' => [
                    'capability' => 'BUY_TICKETS'
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-capability' => [
                'annotation' => CapabilityAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'capability',
                    'getAnnotation' => 'capability',
                    //'getDocblock' => '',
                    //'getValues' => []
                ]
            ],
            'capability-was-not-configured' => [
                'annotation' => CapabilityAnnotation::class,
                'content' => 'UnconfiguredCapability',
                'expected.exception' => InvalidCapabilitySuppliedException::class,
                'expected.exception.asserts' => [
                    'getCapability' => 'UnconfiguredCapability'
                ]
            ]
        ];
    }
}
