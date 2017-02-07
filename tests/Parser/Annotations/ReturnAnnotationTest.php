<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ReturnAnnotation;
use Mill\Parser\Version;

class ReturnAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider annotationProvider
     */
    public function testAnnotation($param, $version, $expected)
    {
        $annotation = new ReturnAnnotation($param, __CLASS__, __METHOD__, $version);

        $this->assertTrue($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertSame($expected['http_code'], $annotation->getHttpCode());
        $this->assertSame($expected['representation'], $annotation->getRepresentation());
        $this->assertFalse($annotation->getCapability());

        if ($expected['version']) {
            $this->assertInstanceOf('\Mill\Parser\Version', $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }
    }

    /**
     * @return array
     */
    public function annotationProvider()
    {
        return [
            'with-no-representation' => [
                'param' => '{deleted}',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => false,
                    'type' => 'deleted',
                    'version' => false
                ]
            ],
            'with-no-representation-and-a-description' => [
                'param' => '{notmodified} If no data has been changed.',
                'version' => null,
                'expected' => [
                    'description' => 'If no data has been changed.',
                    'http_code' => '304 Not Modified',
                    'representation' => false,
                    'type' => 'notmodified',
                    'version' => false
                ]
            ],
            'versioned' => [
                'param' => '{collection} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => new Version('3.2', __CLASS__, __METHOD__),
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'collection',
                    'version' => '3.2'
                ]
            ],
            '_complete' => [
                'param' => '{collection} \Mill\Examples\Showtimes\Representations\Movie A collection of movies.',
                'version' => new Version('3.2', __CLASS__, __METHOD__),
                'expected' => [
                    'description' => 'A collection of movies.',
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'collection',
                    'version' => '3.2'
                ]
            ],

            // 200's
            'collection' => [
                'param' => '{collection} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'collection',
                    'version' => false
                ]
            ],
            'directory' => [
                'param' => '{directory} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'directory',
                    'version' => false
                ]
            ],
            'object' => [
                'param' => '{object} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'object',
                    'version' => false
                ]
            ],
            'ok' => [
                'param' => '{ok} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '200 OK',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'ok',
                    'version' => false
                ]
            ],

            // 201's
            'created' => [
                'param' => '{created} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '201 Created',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'created',
                    'version' => false
                ]
            ],

            // 202's
            'accepted' => [
                'param' => '{accepted} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '202 Accepted',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'accepted',
                    'version' => false
                ]
            ],

            // 204's
            'added' => [
                'param' => '{added} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'added',
                    'version' => false
                ]
            ],
            'deleted' => [
                'param' => '{deleted} \Mill\Examples\Showtimes\Representations\Representation',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Representation',
                    'type' => 'deleted',
                    'version' => false
                ]
            ],
            'exists' => [
                'param' => '{exists} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'exists',
                    'version' => false
                ]
            ],
            'updated' => [
                'param' => '{updated} \Mill\Examples\Showtimes\Representations\Movie',
                'version' => null,
                'expected' => [
                    'description' => false,
                    'http_code' => '204 No Content',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'updated',
                    'version' => false
                ]
            ],

            // 304's
            'notModified' => [
                'param' => '{notmodified} \Mill\Examples\Showtimes\Representations\Movie If no data has changed.',
                'version' => null,
                'expected' => [
                    'description' => 'If no data has changed.',
                    'http_code' => '304 Not Modified',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'type' => 'notmodified',
                    'version' => false
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function badAnnotationProvider()
    {
        return [
            'code-could-not-be-found' => [
                'annotation' => '\Mill\Parser\Annotations\ReturnAnnotation',
                'docblock' => '\Mill\Examples\Showtimes\Representations\Movie',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/code/'
                ]
            ],
            'code-is-invalid' => [
                'annotation' => '\Mill\Parser\Annotations\ReturnAnnotation',
                'docblock' => '{200 OK} \Mill\Examples\Showtimes\Representations\Movie',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\UnknownReturnCodeException',
                'expected.exception.regex' => [
                    '/200 OK/'
                ]
            ],
            'representation-is-unknown' => [
                'annotation' => '\Mill\Parser\Annotations\ReturnAnnotation',
                'docblock' => '{object} \UnknownRepresentation',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\UnknownRepresentationException',
                'expected.exception.regex' => []
            ]
        ];
    }
}
