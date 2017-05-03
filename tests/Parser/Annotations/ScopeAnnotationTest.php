<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ScopeAnnotation;

class ScopeAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, array $expected)
    {
        $annotation = new ScopeAnnotation($content, __CLASS__, __METHOD__);

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
                'content' => 'edit',
                'expected' => [
                    'description' => false,
                    'scope' => 'edit'
                ]
            ],
            '_complete' => [
                'content' => 'create Create scope is required for this action!',
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
    public function providerAnnotationFailsOnInvalidContent()
    {
        return [
            'missing-scope' => [
                'annotation' => '\Mill\Parser\Annotations\ScopeAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'scope',
                    'getAnnotation' => 'scope',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'scope-was-not-configured' => [
                'annotation' => '\Mill\Parser\Annotations\ScopeAnnotation',
                'content' => 'unknownScope',
                'expected.exception' => '\Mill\Exceptions\Annotations\InvalidScopeSuppliedException',
                'expected.exception.asserts' => [
                    'getScope' => 'unknownScope',
                    'getAnnotation' => null
                ]
            ]
        ];
    }
}
