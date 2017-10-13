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
     * @return void
     */
    public function testAnnotation(string $content, bool $visible, bool $deprecated, array $expected): void
    {
        $annotation = (new UriAnnotation($content, __CLASS__, __METHOD__))->process();
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
     * @return void
     */
    public function testHydrate(string $content, bool $visible, bool $deprecated, array $expected): void
    {
        $annotation = UriAnnotation::hydrate(array_merge(
            $expected['array'],
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @param UriAnnotation $annotation
     * @param array $expected
     * @return void
     */
    private function assertAnnotation(UriAnnotation $annotation, array $expected): void
    {
        $this->assertTrue($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertTrue($annotation->supportsDeprecation());
        $this->assertTrue($annotation->supportsAliasing());

        $this->assertSame($expected['array'], $annotation->toArray());
        $this->assertSame($expected['clean.path'], $annotation->getCleanPath());
        $this->assertFalse($annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    /**
     * @return void
     */
    public function testConfiguredUriSegmentTranslations(): void
    {
        $this->getConfig()->addUriSegmentTranslation('movie_id', 'id');

        $annotation = (new UriAnnotation(
            '{Movies\Showtimes} /movies/+movie_id/showtimes',
            __CLASS__,
            __METHOD__
        ))->process();

        $this->assertSame('/movies/{id}/showtimes', $annotation->getCleanPath());
        $this->assertSame('/movies/+movie_id/showtimes', $annotation->toArray()['path']);
    }

    /**
     * @return array
     */
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
                        'group' => 'Movies\Showtimes',
                        'path' => '/movies/+id/showtimes',
                        'visible' => false
                    ]
                ]
            ],
            'private.group_with_no_depth' => [
                'content' => '{Movies} /movies',
                'visible' => false,
                'deprecated' => false,
                'expected' => [
                    'clean.path' => '/movies',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => false,
                        'group' => 'Movies',
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
                        'group' => 'Movies\Showtimes',
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
                        'group' => 'Movies\Showtimes',
                        'path' => '/movies/+id/showtimes',
                        'visible' => true
                    ]
                ]
            ],
            'public.non-alphanumeric_group' => [
                'content' => '{/} /',
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'clean.path' => '/',
                    'array' => [
                        'aliased' => false,
                        'aliases' => [],
                        'deprecated' => false,
                        'group' => '/',
                        'path' => '/',
                        'visible' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-group' => [
                'annotation' => UriAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'group',
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
