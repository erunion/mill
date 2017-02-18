<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\CapabilityAnnotation;

class CapabilityAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($capability, $expected)
    {
        $annotation = new CapabilityAnnotation($capability, __CLASS__, __METHOD__);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['capability'], $annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            '_complete' => [
                'capability' => 'BUY_TICKETS',
                'expected' => [
                    'capability' => 'BUY_TICKETS'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidAnnotations()
    {
        return [
            'missing-capability' => [
                'annotation' => '\Mill\Parser\Annotations\CapabilityAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/api-capability/'
                ]
            ],
            'capability-was-not-configured' => [
                'annotation' => '\Mill\Parser\Annotations\CapabilityAnnotation',
                'docblock' => 'UnconfiguredCapability',
                'expected.exception' => '\Mill\Exceptions\InvalidCapabilitySuppliedException',
                'expected.exception.regex' => []
            ]
        ];
    }
}
