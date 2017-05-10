<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\UriAnnotation;

class UriAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param boolean $visible
     * @param boolean $deprecated
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, $visible, $deprecated, array $expected)
    {
        $annotation = new UriAnnotation($content, __CLASS__, __METHOD__);
        $annotation->setVisibility($visible);
        $annotation->setDeprecated($deprecated);

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

    public function testConfiguredUriSegmentTranslations()
    {
        $this->getConfig()->addUriSegmentTranslation('movie_id', 'id');

        $annotation = new UriAnnotation('{Movies\Showtimes} /movies/+movie_id/showtimes', __CLASS__, __METHOD__);

        $this->assertSame('/movies/{id}/showtimes', $annotation->getCleanPath());
        $this->assertSame('/movies/+movie_id/showtimes', $annotation->toArray()['path']);
    }

    /**
     * @return array
     */
    public function providerAnnotation()
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
    public function providerAnnotationFailsOnInvalidContent()
    {
        return [
            'missing-group' => [
                'annotation' => '\Mill\Parser\Annotations\UriAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'group',
                    'getAnnotation' => 'uri',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'missing-path' => [
                'annotation' => '\Mill\Parser\Annotations\UriAnnotation',
                'content' => '{Movies}',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
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
