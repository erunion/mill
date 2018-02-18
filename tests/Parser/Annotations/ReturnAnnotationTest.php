<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Exceptions\Annotations\UnknownRepresentationException;
use Mill\Exceptions\Annotations\UnknownReturnCodeException;
use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Version;

class ReturnAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param bool $visible
     * @param $version
     * @param array $expected
     */
    public function testAnnotation(string $content, bool $visible, $version, array $expected): void
    {
        $annotation = new ReturnAnnotation($content, __CLASS__, __METHOD__, $version);
        $annotation->process();
        $annotation->setVisibility($visible);

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param bool $visible
     * @param $version
     * @param array $expected
     */
    public function testHydrate(string $content, bool $visible, $version, array $expected): void
    {
        $annotation = ReturnAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ), $version);

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(ReturnAnnotation $annotation, array $expected): void
    {
        $this->assertTrue($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertSame($expected['http_code'], $annotation->getHttpCode());
        $this->assertSame($expected['representation'], $annotation->getRepresentation());
        $this->assertSame($expected['type'], $annotation->getType());
        $this->assertFalse($annotation->getCapability());

        if ($expected['version']) {
            $this->assertInstanceOf(Version::class, $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }

        $this->assertEmpty($annotation->getAliases());
    }

    public function providerAnnotation(): array
    {
        return [
            'with-no-representation' => [
                'content' => '(deleted)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => false,
                    'type' => 'deleted',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'with-no-representation-and-a-description' => [
                'content' => '(notmodified) If no data has been changed.',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => 'If no data has been changed.',
                    'http_code' => '304 Not Modified',
                    'representation' => false,
                    'type' => 'notmodified',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'versioned' => [
                'content' => '(collection, Movie)',
                'visible' => true,
                'version' => new Version('3.2', __CLASS__, __METHOD__),
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => 'Movie',
                    'type' => 'collection',
                    'version' => '3.2',
                    'visible' => true
                ]
            ],
            '_complete' => [
                'content' => '(collection, Movie) - A collection of movies.',
                'visible' => true,
                'version' => new Version('3.2', __CLASS__, __METHOD__),
                'expected' => [
                    'description' => 'A collection of movies.',
                    'http_code' => '200 OK',
                    'representation' => 'Movie',
                    'type' => 'collection',
                    'version' => '3.2',
                    'visible' => true
                ]
            ],

            // 200's
            'collection' => [
                'content' => '(collection, Movie)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => 'Movie',
                    'type' => 'collection',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'directory' => [
                'content' => '(directory, Movie)',
                'visible' => true,
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => 'Movie',
                    'type' => 'directory',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'object' => [
                'content' => '(object, Movie)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => 'Movie',
                    'type' => 'object',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'ok' => [
                'content' => '(ok, Movie)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => 'Movie',
                    'type' => 'ok',
                    'version' => false,
                    'visible' => true
                ]
            ],

            // 201's
            'created' => [
                'content' => '(created, Movie)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '201 Created',
                    'representation' => 'Movie',
                    'type' => 'created',
                    'version' => false,
                    'visible' => true
                ]
            ],

            // 202's
            'accepted' => [
                'content' => '(accepted, Movie)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '202 Accepted',
                    'representation' => 'Movie',
                    'type' => 'accepted',
                    'version' => false,
                    'visible' => true
                ]
            ],

            // 204's
            'added' => [
                'content' => '(added, Movie)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => 'Movie',
                    'type' => 'added',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'deleted' => [
                'content' => '(deleted, Representation)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => 'Representation',
                    'type' => 'deleted',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'exists' => [
                'content' => '(exists, Movie)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => 'Movie',
                    'type' => 'exists',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'updated' => [
                'content' => '(updated, Movie)',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => 'Movie',
                    'type' => 'updated',
                    'version' => false,
                    'visible' => true
                ]
            ],

            // 304's
            'notModified' => [
                'content' => '(notmodified, Movie) If no data has changed.',
                'visible' => true,
                'version' => null,
                'expected' => [
                    'description' => 'If no data has changed.',
                    'http_code' => '304 Not Modified',
                    'representation' => 'Movie',
                    'type' => 'notmodified',
                    'version' => false,
                    'visible' => true
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'code-could-not-be-found' => [
                'annotation' => ReturnAnnotation::class,
                'content' => 'Movie',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'http_code',
                    'getAnnotation' => 'return',
                    'getDocblock' => 'Movie',
                    'getValues' => []
                ]
            ],
            'code-is-invalid' => [
                'annotation' => ReturnAnnotation::class,
                'content' => '{200 OK, Movie)',
                'expected.exception' => UnknownReturnCodeException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '{200 OK, Movie)',
                    'getValues' => []
                ]
            ],
            'representation-is-unknown' => [
                'annotation' => ReturnAnnotation::class,
                'content' => '{object, UnknownRepresentation)',
                'expected.exception' => UnknownRepresentationException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '(object, UnknownRepresentation)',
                    'getValues' => []
                ]
            ]
        ];
    }
}
