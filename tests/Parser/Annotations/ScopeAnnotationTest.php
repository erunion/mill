<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\InvalidScopeSuppliedException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\ScopeAnnotation;
use Mill\Parser\Reader\Docblock;

class ScopeAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     */
    public function testAnnotation(string $content, array $expected): void
    {
        $docblock = new Docblock($content, __FILE__, 0, strlen($content));
        $annotation = new ScopeAnnotation($this->application, $content, $docblock);
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
        $annotation = ScopeAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }*/

    private function assertAnnotation(ScopeAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['scope'], $annotation->getScope());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertFalse($annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    public function providerAnnotation(): array
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

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-scope' => [
                'annotation' => ScopeAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'scope',
                    'getAnnotation' => 'scope',
                    //'getDocblock' => '',
                    //'getValues' => []
                ]
            ],
            'scope-was-not-configured' => [
                'annotation' => ScopeAnnotation::class,
                'content' => 'unknownScope',
                'expected.exception' => InvalidScopeSuppliedException::class,
                'expected.exception.asserts' => [
                    'getScope' => 'unknownScope',
                    'getAnnotation' => null
                ]
            ]
        ];
    }
}
