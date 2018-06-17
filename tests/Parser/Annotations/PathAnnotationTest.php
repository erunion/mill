<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\PathAnnotation;

class PathAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param bool $visible
     * @param bool $deprecated
     * @param array $expected
     */
    public function testAnnotation(string $content, bool $visible, bool $deprecated, array $expected): void
    {
        $annotation = new PathAnnotation($content, __CLASS__, __METHOD__);
        $annotation->process();
        $annotation->setVisibility($visible);
        $annotation->setDeprecated($deprecated);

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(PathAnnotation $annotation, array $expected): void
    {
        $this->assertTrue($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsVendorTags());
        $this->assertTrue($annotation->requiresVisibilityDecorator());

        $this->assertSame($expected['array'], $annotation->toArray());
        $this->assertSame($expected['clean.path'], $annotation->getCleanPath());
        $this->assertEmpty($annotation->getVendorTags());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    public function testConfiguredPathParamTranslations(): void
    {
        $this->getConfig()->addPathParamTranslation('movie_id', 'id');

        $annotation = new PathAnnotation('/movies/+movie_id/showtimes', __CLASS__, __METHOD__);
        $annotation->process();

        $this->assertSame('/movies/{id}/showtimes', $annotation->getCleanPath());
        $this->assertSame('/movies/+id/showtimes', $annotation->toArray()['path']);
    }

    public function providerAnnotation(): array
    {
        return [
            'private' => [
                'content' => '/movies/+id/showtimes',
                'visible' => false,
                'deprecated' => false,
                'expected' => [
                    'clean.path' => '/movies/{id}/showtimes',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => false,
                        'path' => '/movies/+id/showtimes',
                        'visible' => false
                    ]
                ]
            ],
            'public' => [
                'content' => '/movies/+id/showtimes',
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'clean.path' => '/movies/{id}/showtimes',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => false,
                        'path' => '/movies/+id/showtimes',
                        'visible' => true
                    ]
                ]
            ],
            'public.deprecated' => [
                'content' => '/movies/+id/showtimes',
                'visible' => true,
                'deprecated' => true,
                'expected' => [
                    'clean.path' => '/movies/{id}/showtimes',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => true,
                        'path' => '/movies/+id/showtimes',
                        'visible' => true
                    ]
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-path' => [
                'annotation' => PathAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'path',
                    'getAnnotation' => 'path',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ]
        ];
    }
}
