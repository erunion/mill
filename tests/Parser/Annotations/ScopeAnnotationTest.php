<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ScopeAnnotation;

class ScopeAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($param, $expected)
    {
        $annotation = new ScopeAnnotation($param, __CLASS__, __METHOD__);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertSame($expected, $annotation->toArray());

        $this->assertSame($expected['scope'], $annotation->getScope());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertFalse($annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            'bare' => [
                'param' => 'edit',
                'expected' => [
                    'description' => false,
                    'scope' => 'edit'
                ]
            ],
            '_complete' => [
                'param' => 'create Create scope is required for this action!',
                'expected' => [
                    'description' => 'Create scope is required for this action!',
                    'scope' => 'create'
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
            'missing-scope' => [
                'annotation' => '\Mill\Parser\Annotations\ScopeAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`scope`/'
                ]
            ],
            'scope-was-not-configured' => [
                'annotation' => '\Mill\Parser\Annotations\ScopeAnnotation',
                'docblock' => 'unknownScope',
                'expected.exception' => '\Mill\Exceptions\InvalidScopeSuppliedException',
                'expected.exception.regex' => []
            ]
        ];
    }
}
