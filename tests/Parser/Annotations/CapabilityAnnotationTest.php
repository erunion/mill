<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\CapabilityAnnotation;

class CapabilityAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, array $expected)
    {
        $annotation = new CapabilityAnnotation($content, __CLASS__, __METHOD__);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['capability'], $annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
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

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidContent()
    {
        return [
            'missing-capability' => [
                'annotation' => '\Mill\Parser\Annotations\CapabilityAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'capability',
                    'getAnnotation' => 'capability',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'capability-was-not-configured' => [
                'annotation' => '\Mill\Parser\Annotations\CapabilityAnnotation',
                'content' => 'UnconfiguredCapability',
                'expected.exception' => '\Mill\Exceptions\Annotations\InvalidCapabilitySuppliedException',
                'expected.exception.asserts' => [
                    'getCapability' => 'UnconfiguredCapability'
                ]
            ]
        ];
    }
}
