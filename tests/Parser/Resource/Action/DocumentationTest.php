<?php
namespace Mill\Tests\Parser\Resource\Action;

use Mill\Parser\Resource\Action\Documentation;
use Mill\Tests\TestCase;

class DocumentationTest extends TestCase
{
    /**
     * @dataProvider annotationsProvider
     */
    public function testParseMethodDocumentation($method, $expected)
    {
        $controller_stub = '\Mill\Examples\Showtimes\Controllers\Movie';
        $parser = (new Documentation($controller_stub, $method))->parse();

        $this->assertSame($controller_stub, $parser->getController());
        $this->assertSame($method, $parser->getMethod());

        $this->assertSame($expected['label'], $parser->getLabel());
        $this->assertSame($expected['content_type'], $parser->getContentType());

        /** @var \Mill\Parser\Annotations\MinVersionAnnotation $min_version */
        $min_version = $parser->getMinimumVersion();
        if ($expected['minimum_version']) {
            $this->assertInstanceOf('\Mill\Parser\Annotations\MinVersionAnnotation', $min_version);
            $this->assertSame($expected['minimum_version'], $min_version->getMinimumVersion());
        } else {
            $this->assertNull($min_version);
        }

        if (!isset($expected['annotations']['scope'])) {
            $this->assertEmpty($parser->getScopes());
        } else {
            $this->assertCount(count($expected['annotations']['scope']), $parser->getScopes());
        }

        if (!isset($expected['annotations']['param'])) {
            $this->assertEmpty($parser->getParameters());
        } else {
            $this->assertCount(count($expected['annotations']['param']), $parser->getParameters());
        }

        $this->assertSame($expected['responses.length'], count($parser->getResponses()));

        $docs = $parser->toArray();
        $this->assertSame($expected['label'], $docs['label']);
        $this->assertSame($docs['description'], $parser->getDescription());
        $this->assertSame($expected['description.length'], strlen($docs['description']));
        $this->assertSame($method, $docs['method']);
        $this->assertSame($expected['content_type'], $docs['content_type']);

        if (empty($docs['annotations'])) {
            $this->fail('No parsed annotations for ' . $controller_stub);
        }

        foreach ($docs['annotations'] as $name => $data) {
            if (!isset($expected['annotations'][$name])) {
                $this->fail('A parsed `' . $name . '` annotation was not present in the expected data.');
            }

            foreach ($data as $k => $annotation) {
                $this->assertSame($expected['annotations'][$name][$k], $annotation, '`' . $name . '` mismatch');
            }
        }
    }

    /**
     * @dataProvider badMethodsProvider
     */
    public function testMethodsThatWillFailParsing($method, $exception, $regex)
    {
        $this->expectException($exception);
        foreach ($regex as $rule) {
            $this->expectExceptionMessageRegExp($rule);
        }

        $controller = '\Mill\Tests\Fixtures\Controllers\ControllerWithBadMethods';
        (new Documentation($controller, $method))->parse()->toArray();
    }

    /**
     * @return array
     */
    public function annotationsProvider()
    {
        return [
            'GET' => [
                'method' => 'GET',
                'expected' => [
                    'label' => 'Get a single movie.',
                    'description.length' => 39,
                    'content_type' => 'application/json',
                    'minimum_version' => false,
                    'responses.length' => 2,
                    'annotations' => [
                        'uri' => [
                            [
                                'deprecated' => false,
                                'group' => 'Movies',
                                'path' => '/movies/+id',
                                'visible' => true
                            ]
                        ],
                        'uriSegment' => [
                            [
                                'description' => 'Movie ID',
                                'field' => 'id',
                                'type' => 'integer',
                                'uri' => '/movies/+id',
                                'values' => false
                            ]
                        ],
                        'return' => [
                            [
                                'description' => false,
                                'http_code' => '200 OK',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                                'type' => 'object',
                                'version' => false
                            ]
                        ],
                        'scope' => [
                            [
                                'description' => false,
                                'scope' => 'public'
                            ]
                        ],
                        'throws' => [
                            [
                                'capability' => false,
                                'description' => 'If the movie could not be found.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => false,
                                'visible' => true
                            ]
                        ]
                    ]
                ]
            ],
            'PATCH' => [
                'method' => 'PATCH',
                'expected' => [
                    'label' => 'Update a movie.',
                    'description.length' => 21,
                    'content_type' => 'application/json',
                    'minimum_version' => '1.1',
                    'responses.length' => 3,
                    'annotations' => [
                        'uri' => [
                            [
                                'deprecated' => false,
                                'group' => 'Movies',
                                'path' => '/movies/+id',
                                'visible' => true
                            ]
                        ],
                        'uriSegment' => [
                            [
                                'description' => 'Movie ID',
                                'field' => 'id',
                                'type' => 'integer',
                                'uri' => '/movies/+id',
                                'values' => false
                            ]
                        ],
                        'minVersion' => [
                            [
                                'minimum_version' => '1.1'
                            ]
                        ],
                        'param' => [
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Name of the movie.',
                                'field' => 'name',
                                'required' => true,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Description, or tagline, for the movie.',
                                'field' => 'description',
                                'required' => true,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Movie runtime, in `HHhr MMmin` format.',
                                'field' => 'runtime',
                                'required' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'MPAA rating',
                                'field' => 'content_rating',
                                'required' => false,
                                'type' => 'string',
                                'values' => [
                                    'G',
                                    'PG',
                                    'PG-13',
                                    'R',
                                    'NC-17',
                                    'X',
                                    'NR',
                                    'UR'
                                ],
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Array of movie genres.',
                                'field' => 'genres',
                                'required' => false,
                                'type' => 'array',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'IMDB URL',
                                'field' => 'imdb',
                                'required' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Trailer URL',
                                'field' => 'trailer',
                                'required' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Name of the director.',
                                'field' => 'director',
                                'required' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Array of names of the cast.',
                                'field' => 'cast',
                                'required' => false,
                                'type' => 'array',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'return' => [
                            [
                                'description' => false,
                                'http_code' => '200 OK',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                                'type' => 'object',
                                'version' => false
                            ]
                        ],
                        'scope' => [
                            [
                                'description' => false,
                                'scope' => 'edit'
                            ]
                        ],
                        'throws' => [
                            [
                                'capability' => false,
                                'description' => 'If there is a problem with the request.',
                                'error_code' => false,
                                'http_code' => '400 Bad Request',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'description' => 'If the movie could not be found.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => false,
                                'visible' => true
                            ]
                        ]
                    ]
                ]
            ],
            'DELETE' => [
                'method' => 'DELETE',
                'expected' => [
                    'label' => 'Delete a movie.',
                    'description.length' => 15,
                    'content_type' => 'application/json',
                    'minimum_version' => false,
                    'responses.length' => 2,
                    'annotations' => [
                        'uri' => [
                            [
                                'deprecated' => false,
                                'group' => 'Movies',
                                'path' => '/movies/+id',
                                'visible' => false
                            ]
                        ],
                        'uriSegment' => [
                            [
                                'description' => 'Movie ID',
                                'field' => 'id',
                                'type' => 'integer',
                                'uri' => '/movies/+id',
                                'values' => false
                            ]
                        ],
                        'return' => [
                            [
                                'description' => false,
                                'http_code' => '204 No Content',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Representation',
                                'type' => 'deleted',
                                'version' => false
                            ]
                        ],
                        'scope' => [
                            [
                                'description' => false,
                                'scope' => 'delete'
                            ]
                        ],
                        'throws' => [
                            [
                                'capability' => false,
                                'description' => 'If the movie could not be found.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => false,
                                'visible' => false
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function badMethodsProvider()
    {
        return [
            'no-parsed-annotations' => [
                'method' => 'withNoParsedAnnotations',
                'expected.exception' => '\Mill\Exceptions\Resource\NoAnnotationsException',
                'expected.exception.regex' => []
            ],
            'missing-required-label-annotation' => [
                'method' => 'withMissingRequiredLabelAnnotation',
                'expected.exception' => '\Mill\Exceptions\RequiredAnnotationException',
                'expected.exception.regex' => []
            ],
            'multiple-label-annotations' => [
                'method' => 'withMultipleLabelAnnotations',
                'expected.exception' => '\Mill\Exceptions\MultipleAnnotationsException',
                'expected.exception.regex' => [
                    '/api-label/'
                ]
            ],
            'missing-required-content-type-annotation' => [
                'method' => 'withMissingRequiredContentTypeAnnotation',
                'expected.exception' => '\Mill\Exceptions\RequiredAnnotationException',
                'expected.exception.regex' => [
                    '/api-contentType/'
                ]
            ],
            'multiple-content-type-annotations' => [
                'method' => 'withMultipleContentTypeAnnotations',
                'expected.exception' => '\Mill\Exceptions\MultipleAnnotationsException',
                'expected.exception.regex' => [
                    '/api-contentType/'
                ]
            ],
            'missing-required-visibility-decorator' => [
                'method' => 'withMissingRequiredVisibilityDecorator',
                'expected.exception' => '\Mill\Exceptions\Resource\MissingVisibilityDecoratorException',
                'expected.exception.regex' => [
                    '/api-uri/'
                ]
            ],
            'unsupported-decorator' => [
                'method' => 'withUnsupportedDecorator',
                'expected.exception' => '\Mill\Exceptions\Resource\UnsupportedDecoratorException',
                'expected.exception.regex' => [
                    '/special/',
                    '/uri/'
                ]
            ],
            'required-uri-annotation-missing' => [
                'method' => 'withRequiredUriAnnotationMissing',
                'expected.exception' => '\Mill\Exceptions\RequiredAnnotationException',
                'expected.exception.regex' => [
                    '/api-uri/'
                ]
            ],
            'public-annotations-on-a-private-action' => [
                'method' => 'withPublicAnnotationsOnAPrivateAction',
                'expected.exception' => '\Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException',
                'expected.exception.regex' => [
                    '/api-throws/'
                ]
            ]
        ];
    }
}
