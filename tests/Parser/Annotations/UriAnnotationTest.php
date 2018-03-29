<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Annotations\UriAnnotation;

class UriAnnotationTest extends AnnotationTest
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
        $annotation = new UriAnnotation($content, __CLASS__, __METHOD__);
        $annotation->process();
        $annotation->setVisibility($visible);
        $annotation->setDeprecated($deprecated);

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param bool $visible
     * @param bool $deprecated
     * @param array $expected
     */
    public function testHydrate(string $content, bool $visible, bool $deprecated, array $expected): void
    {
        /** @var UriAnnotation $annotation */
        $annotation = UriAnnotation::hydrate(array_merge(
            $expected['array'],
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(UriAnnotation $annotation, array $expected): void
    {
        $this->assertTrue($annotation->supportsAliasing());
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

    public function testConfiguredUriSegmentTranslations(): void
    {
        $this->getConfig()->addUriSegmentTranslation('movie_id', 'id');

        $annotation = new UriAnnotation('{Movies\Showtimes} /movies/+movie_id/showtimes', __CLASS__, __METHOD__);
        $annotation->process();

        $this->assertSame('/movies/{id}/showtimes', $annotation->getCleanPath());
        $this->assertSame('/movies/+movie_id/showtimes', $annotation->toArray()['path']);
    }

    public function providerAnnotation(): array
    {
        return [
            'private' => [
                'content' => '{Movies\Showtimes} /movies/+id/showtimes',
                'visible' => false,
                'deprecated' => false,
                'expected' => [
                    'clean.path' => '/movies/{id}/showtimes',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => false,
                        'namespace' => 'Movies\Showtimes',
                        'path' => '/movies/+id/showtimes',
                        'visible' => false
                    ]
                ]
            ],
            'private.namespace_with_no_depth' => [
                'content' => '{Movies} /movies',
                'visible' => false,
                'deprecated' => false,
                'expected' => [
                    'clean.path' => '/movies',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => false,
                        'namespace' => 'Movies',
                        'path' => '/movies',
                        'visible' => false
                    ]
                ]
            ],
            'public' => [
                'content' => '{Movies\Showtimes} /movies/+id/showtimes',
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'clean.path' => '/movies/{id}/showtimes',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => false,
                        'namespace' => 'Movies\Showtimes',
                        'path' => '/movies/+id/showtimes',
                        'visible' => true
                    ]
                ]
            ],
            'public.deprecated' => [
                'content' => '{Movies\Showtimes} /movies/+id/showtimes',
                'visible' => true,
                'deprecated' => true,
                'expected' => [
                    'clean.path' => '/movies/{id}/showtimes',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => true,
                        'namespace' => 'Movies\Showtimes',
                        'path' => '/movies/+id/showtimes',
                        'visible' => true
                    ]
                ]
            ],
            'public.non-alphanumeric_namespace' => [
                'content' => '{/} /',
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'clean.path' => '/',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => false,
                        'namespace' => '/',
                        'path' => '/',
                        'visible' => true
                    ]
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-namespace' => [
                'annotation' => UriAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'namespace',
                    'getAnnotation' => 'uri',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'missing-path' => [
                'annotation' => UriAnnotation::class,
                'content' => '{Movies}',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'path',
                    'getAnnotation' => 'uri',
                    'getDocblock' => '{Movies}',
                    'getValues' => []
                ]
            ]
        ];
    }
}
