<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Version;

class ReturnAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param string $version
     * @param boolean $visible
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, $version, $visible, array $expected)
    {
        $annotation = new ReturnAnnotation($content, __CLASS__, __METHOD__, $version);
        $annotation->setVisibility($visible);

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
            $this->assertInstanceOf('\Mill\Parser\Version', $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }

        $this->assertEmpty($annotation->getAliases());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            'with-no-representation' => [
                'content' => '{deleted}',
                'version' => null,
                'visible' => true,
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
                'content' => '{notmodified} If no data has been changed.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => 'If no data has been changed.',
                    'http_code' => '304 Not Modified',
                    'representation' => false,
                    'type' => 'notmodified',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'private' => [
                'content' => '{notmodified} If no data has been changed.',
                'version' => null,
                'visible' => false,
                'expected' => [
                    'description' => 'If no data has been changed.',
                    'http_code' => '304 Not Modified',
                    'representation' => false,
                    'type' => 'notmodified',
                    'version' => false,
                    'visible' => false
                ]
            ],
            'versioned' => [
                'content' => '{collection} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => new Version('3.2', __CLASS__, __METHOD__),
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'collection',
                    'version' => '3.2',
                    'visible' => true
                ]
            ],
            '_complete' => [
                'content' => '{collection} \Mill\Examples\Showtimes\Representations\Movie A collection of movies.',
                'version' => new Version('3.2', __CLASS__, __METHOD__),
                'visible' => true,
                'expected' => [
                    'description' => 'A collection of movies.',
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'collection',
                    'version' => '3.2',
                    'visible' => true
                ]
            ],

            // 200's
            'collection' => [
                'content' => '{collection} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'collection',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'directory' => [
                'content' => '{directory} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'directory',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'object' => [
                'content' => '{object} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'object',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'ok' => [
                'content' => '{ok} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'ok',
                    'version' => false,
                    'visible' => true
                ]
            ],

            // 201's
            'created' => [
                'content' => '{created} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '201 Created',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'created',
                    'version' => false,
                    'visible' => true
                ]
            ],

            // 202's
            'accepted' => [
                'content' => '{accepted} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '202 Accepted',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'accepted',
                    'version' => false,
                    'visible' => true
                ]
            ],

            // 204's
            'added' => [
                'content' => '{added} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'added',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'deleted' => [
                'content' => '{deleted} \Mill\Examples\Showtimes\Representations\Representation',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Representation',
                    'type' => 'deleted',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'exists' => [
                'content' => '{exists} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'exists',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'updated' => [
                'content' => '{updated} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'updated',
                    'version' => false,
                    'visible' => true
                ]
            ],

            // 304's
            'notModified' => [
                'content' => '{notmodified} \Mill\Examples\Showtimes\Representations\Movie If no data has changed.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'description' => 'If no data has changed.',
                    'http_code' => '304 Not Modified',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'notmodified',
                    'version' => false,
                    'visible' => true
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
            'code-could-not-be-found' => [
                'annotation' => '\Mill\Parser\Annotations\ReturnAnnotation',
                'content' => '\Mill\Examples\Showtimes\Representations\Movie',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'http_code',
                    'getAnnotation' => 'return',
                    'getDocblock' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'getValues' => []
                ]
            ],
            'code-is-invalid' => [
                'annotation' => '\Mill\Parser\Annotations\ReturnAnnotation',
                'content' => '{200 OK} \Mill\Examples\Showtimes\Representations\Movie',
                'expected.exception' => '\Mill\Exceptions\Annotations\UnknownReturnCodeException',
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '{200 OK} \Mill\Examples\Showtimes\Representations\Movie',
                    'getValues' => []
                ]
            ],
            'representation-is-unknown' => [
                'annotation' => '\Mill\Parser\Annotations\ReturnAnnotation',
                'content' => '{object} \UnknownRepresentation',
                'expected.exception' => '\Mill\Exceptions\Annotations\UnknownRepresentationException',
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '\UnknownRepresentation',
                    'getValues' => []
                ]
            ]
        ];
    }
}
