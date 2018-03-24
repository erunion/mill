<?php
namespace Mill\Tests\Parser\Resource\Action;

use Mill\Exceptions\BaseException;
use Mill\Parser\Annotations\MinVersionAnnotation;
use Mill\Parser\Resource\Action\Documentation;
use Mill\Parser\Version;
use Mill\Tests\ReaderTestingTrait;
use Mill\Tests\TestCase;

class DocumentationTest extends TestCase
{
    use ReaderTestingTrait;

    /**
     * @dataProvider providerParseMethodDocumentation
     * @param string $method
     * @param array $expected
     */
    public function testParseMethodDocumentation(string $method, array $expected): void
    {
        $class_stub = '\Mill\Examples\Showtimes\Controllers\Movie';
        $parser = (new Documentation($class_stub, $method))->parse();

        $this->assertMethodDocumentation($parser, $class_stub, $method, $expected);
    }

    /**
     * @dataProvider providerParseMethodDocumentation
     * @param string $method
     * @param array $expected
     */
    public function testHydrate(string $method, array $expected): void
    {
        $class_stub = '\Mill\Examples\Showtimes\Controllers\Movie';
        $parser = (new Documentation($class_stub, $method))->parse();
        $docs = $parser->toArray();

        $hydrate = Documentation::hydrate($docs);

        $this->assertMethodDocumentation($hydrate, $class_stub, $method, $expected);
    }

    private function assertMethodDocumentation(
        Documentation $parser,
        string $class,
        string $method,
        array $expected
    ): void {
        $this->assertSame($class, $parser->getClass());
        $this->assertSame($method, $parser->getMethod());

        $this->assertSame($expected['label'], $parser->getLabel());

        $this->assertCount(count($expected['content_types']), $parser->getContentTypes());
        $this->assertSame($expected['content_types'][0]['content_type'], $parser->getContentType());

        if ($expected['content_types.latest-version']) {
            $this->assertSame(
                $expected['content_types'][0]['content_type'],
                $parser->getContentType(
                    new Version($expected['content_types.latest-version'], __CLASS__, __METHOD__)
                )
            );
        }

        $this->assertCount($expected['capabilities.total'], $parser->getCapabilities());

        /** @var \Mill\Parser\Annotations\MinVersionAnnotation $min_version */
        $min_version = $parser->getMinimumVersion();
        if ($expected['minimum_version']) {
            $this->assertInstanceOf(MinVersionAnnotation::class, $min_version);
            $this->assertSame($expected['minimum_version'], $min_version->getMinimumVersion());
        } else {
            $this->assertNull($min_version);
        }

        $this->assertCount(count($expected['annotations']), $parser->getAnnotations());

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
        $this->assertSame($class, $docs['class']);
        $this->assertSame($expected['label'], $docs['label']);
        $this->assertSame($docs['description'], $parser->getDescription());
        $this->assertSame($expected['description'], $docs['description']);
        $this->assertSame($method, $docs['method']);
        $this->assertSame($expected['content_types'], $docs['content_types']);

        if (empty($docs['annotations'])) {
            $this->fail('No parsed annotations for ' . $class);
        }

        foreach ($docs['annotations'] as $name => $data) {
            if (!isset($expected['annotations'][$name])) {
                $this->fail('Parsed `' . $name . '` annotations were not present in the expected data.');
            }

            foreach ($data as $k => $annotation) {
                $annotation_key = $k;
                if ($name === 'param') {
                    // Param annotations are keyed off of the field name.
                    $annotation_key = $annotation['field'];
                }

                if (!isset($expected['annotations'][$name][$annotation_key])) {
                    $this->fail('A parsed `' . $name . '` annotation was not present in the expected data.');
                }

                $this->assertSame(
                    $expected['annotations'][$name][$annotation_key],
                    $annotation,
                    '`' . $k . '` mismatch'
                );
            }
        }
    }

    /**
     * @dataProvider providerParsingOfSpecificUseCases
     * @param string $docblock
     * @param array $asserts
     */
    public function testParsingOfSpecificUseCases(string $docblock, array $asserts): void
    {
        $this->overrideReadersWithFakeDocblockReturn($docblock);

        $parser = (new Documentation(__CLASS__, __METHOD__))->parse();

        $docs = $parser->toArray();
        $annotations = $docs['annotations'];
        foreach ($asserts as $method => $assert) {
            $this->assertCount($assert['total'], $parser->{$method}());
            $this->assertArrayHasKey($assert['annotation.name'], $annotations);
            $this->assertSame($assert['data'], $annotations[$assert['annotation.name']]);
        }
    }

    /**
     * @dataProvider providerMethodsThatWillFailParsing
     * @param string $docblock
     * @param string $exception
     * @param array $asserts
     * @throws BaseException
     */
    public function testMethodsThatWillFailParsing(string $docblock, string $exception, array $asserts): void
    {
        $this->expectException($exception);
        $this->overrideReadersWithFakeDocblockReturn($docblock);

        try {
            (new Documentation(__CLASS__, __METHOD__))->parse()->toArray();
        } catch (BaseException $e) {
            if ('\\' . get_class($e) !== $exception) {
                $this->fail('Unrecognized exception (' . get_class($e) . ') thrown.');
            }

            $this->assertExceptionAsserts($e, __CLASS__, __METHOD__, $asserts);
            throw $e;
        }
    }

    public function providerParseMethodDocumentation(): array
    {
        $get_description = <<<DESCRIPTION
Return information on a specific movie.

Donec id elit non mi porta gravida at eget metus. Cras mattis consectetur purus sit amet fermentum. Lorem
ipsum dolor sit amet, consectetur adipiscing elit. Etiam porta sem malesuada magna mollis euismod. Duis
mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Etiam porta
sem malesuada magna mollis euismod.

```
[
  {"id": "fizzbuzz"}
]
```
DESCRIPTION;

        return [
            'GET' => [
                'method' => 'GET',
                'expected' => [
                    'label' => 'Get a single movie.',
                    'description' => $get_description,
                    'capabilities.total' => 0,
                    'content_types.latest-version' => '1.1.2',
                    'content_types' => [
                        [
                            'content_type' => 'application/mill.example.movie',
                            'version' => '>=1.1.2'
                        ],
                        [
                            'content_type' => 'application/json',
                            'version' => '<1.1.2'
                        ]
                    ],
                    'minimum_version' => false,
                    'responses.length' => 5,
                    'annotations' => [
                        'error' => [
                            [
                                'capability' => false,
                                'description' => 'If the movie could not be found.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'description' => 'For no reason.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => '>=1.1.3',
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'description' => 'For some other reason.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => '>=1.1.3',
                                'visible' => true
                            ]
                        ],
                        'uri' => [
                            [
                                'aliased' => true,
                                'aliases' => [],
                                'deprecated' => false,
                                'namespace' => 'Movies',
                                'path' => '/movie/+id',
                                'visible' => false
                            ],
                            [
                                'aliased' => false,
                                'aliases' => [
                                    [
                                        'aliased' => true,
                                        'aliases' => [],
                                        'deprecated' => false,
                                        'namespace' => 'Movies',
                                        'path' => '/movie/+id',
                                        'visible' => false
                                    ]
                                ],
                                'deprecated' => false,
                                'namespace' => 'Movies',
                                'path' => '/movies/+id',
                                'visible' => true
                            ]
                        ],
                        'uriSegment' => [
                            [
                                'description' => 'Movie ID',
                                'field' => 'id',
                                'type' => 'integer',
                                'uri' => '/movie/+id',
                                'values' => false
                            ],
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
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'description' => 'If no content has been modified since the supplied Last-Modified ' .
                                    'header.',
                                'http_code' => '304 Not Modified',
                                'representation' => false,
                                'type' => 'notmodified',
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
                    'description' => 'Update a movies data.',
                    'capabilities.total' => 0,
                    'content_types.latest-version' => '1.1.2',
                    'content_types' => [
                        [
                            'content_type' => 'application/mill.example.movie',
                            'version' => '>=1.1.2'
                        ],
                        [
                            'content_type' => 'application/json',
                            'version' => '<1.1.2'
                        ]
                    ],
                    'minimum_version' => '1.1',
                    'responses.length' => 8,
                    'uri.aliases' => [],
                    'annotations' => [
                        'error' => [
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
                                'description' => 'If the IMDB URL could not be validated.',
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
                            ],
                            [
                                'capability' => false,
                                'description' => 'If the trailer URL could not be validated.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => '>=1.1.3',
                                'visible' => true
                            ],
                            [
                                'capability' => false,
                                'description' => 'If something cool happened.',
                                'error_code' => '1337',
                                'http_code' => '403 Forbidden',
                                'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                                'version' => '>=1.1.3',
                                'visible' => false
                            ],
                            [
                                'capability' => false,
                                'description' => 'If the user is not allowed to edit that movie.',
                                'error_code' => '666',
                                'http_code' => '403 Forbidden',
                                'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                                'version' => '>=1.1.3',
                                'visible' => true
                            ]
                        ],
                        'uri' => [
                            [
                                'aliased' => false,
                                'aliases' => [],
                                'deprecated' => false,
                                'namespace' => 'Movies',
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
                            'cast' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Array of names of the cast.',
                                'field' => 'cast',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'array',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            'content_rating' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'MPAA rating',
                                'field' => 'content_rating',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'enum',
                                'values' => [
                                    'G' => '',
                                    'NC-17' => '',
                                    'NR' => '',
                                    'PG' => '',
                                    'PG-13' => '',
                                    'R' => '',
                                    'UR' => '',
                                    'X' => ''
                                ],
                                'version' => false,
                                'visible' => true
                            ],
                            'description' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Description, or tagline, for the movie.',
                                'field' => 'description',
                                'nullable' => false,
                                'required' => true,
                                'sample_data' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            'director' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Name of the director.',
                                'field' => 'director',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            'is_kid_friendly' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Is this movie kid friendly?',
                                'field' => 'is_kid_friendly',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'boolean',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            'name' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Name of the movie.',
                                'field' => 'name',
                                'nullable' => false,
                                'required' => true,
                                'sample_data' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            'genres' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Array of movie genres.',
                                'field' => 'genres',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'array',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            'imdb' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'IMDB URL',
                                'field' => 'imdb',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => '>=1.1.1',
                                'visible' => true
                            ],
                            'rotten_tomatoes_score' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Rotten Tomatoes score',
                                'field' => 'rotten_tomatoes_score',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'integer',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            'runtime' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Movie runtime, in `HHhr MMmin` format.',
                                'field' => 'runtime',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false,
                                'visible' => true
                            ],
                            'trailer' => [
                                'capability' => false,
                                'deprecated' => false,
                                'description' => 'Trailer URL',
                                'field' => 'trailer',
                                'nullable' => true,
                                'required' => false,
                                'sample_data' => false,
                                'type' => 'string',
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
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'description' => false,
                                'http_code' => '202 Accepted',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Movie',
                                'type' => 'accepted',
                                'version' => '>=1.1.3',
                                'visible' => true
                            ]
                        ],
                        'scope' => [
                            [
                                'description' => false,
                                'scope' => 'edit'
                            ]
                        ]
                    ]
                ]
            ],
            'DELETE' => [
                'method' => 'DELETE',
                'expected' => [
                    'label' => 'Delete a movie.',
                    'description' => 'Delete a movie.',
                    'capabilities.total' => 1,
                    'content_types.latest-version' => null,
                    'content_types' => [
                        [
                            'content_type' => 'application/json',
                            'version' => false
                        ]
                    ],
                    'minimum_version' => '1.1',
                    'responses.length' => 2,
                    'uri.aliases' => [],
                    'annotations' => [
                        'capability' => [
                            [
                                'capability' => 'DELETE_CONTENT'
                            ]
                        ],
                        'error' => [
                            [
                                'capability' => false,
                                'description' => 'If the movie could not be found.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'version' => false,
                                'visible' => false
                            ]
                        ],
                        'uri' => [
                            [
                                'aliased' => false,
                                'aliases' => [],
                                'deprecated' => false,
                                'namespace' => 'Movies',
                                'path' => '/movies/+id',
                                'visible' => false
                            ]
                        ],
                        'minVersion' => [
                            [
                                'minimum_version' => '1.1'
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
                                'representation' => false,
                                'type' => 'deleted',
                                'version' => false,
                                'visible' => false
                            ]
                        ],
                        'scope' => [
                            [
                                'description' => false,
                                'scope' => 'delete'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function providerParsingOfSpecificUseCases(): array
    {
        return [
            'with-aliased-uris' => [
                'docblock' => '/**
                  * @api-label Update a piece of content.
                  *
                  * @api-uri:public {Foo\Bar} /foo
                  * @api-uri:private:alias {Foo\Bar} /bar
                  *
                  * @api-contentType application/json
                  * @api-scope public
                  *
                  * @api-return:public {ok}
                  */',
                'asserts' => [
                    'getUris' => [
                        'total' => 2,
                        'annotation.name' => 'uri',
                        'data' => [
                            [
                                'aliased' => false,
                                'aliases' => [
                                    [
                                        'aliased' => true,
                                        'aliases' => [],
                                        'deprecated' => false,
                                        'namespace' => 'Foo\Bar',
                                        'path' => '/bar',
                                        'visible' => false
                                    ]
                                ],
                                'deprecated' => false,
                                'namespace' => 'Foo\Bar',
                                'path' => '/foo',
                                'visible' => true
                            ],
                            [
                                'aliased' => true,
                                'aliases' => [],
                                'deprecated' => false,
                                'namespace' => 'Foo\Bar',
                                'path' => '/bar',
                                'visible' => false
                            ]
                        ]
                    ]
                ]
            ],
            'with-multiple-visibilities' => [
                'docblock' => '/**
                  * @api-label Update a piece of content.
                  *
                  * @api-uri:public {Foo\Bar} /foo
                  * @api-uri:private {Foo\Bar} /bar
                  *
                  * @api-contentType application/json
                  * @api-scope public
                  *
                  * @api-return:public {ok}
                  */',
                'asserts' => [
                    'getUris' => [
                        'total' => 2,
                        'annotation.name' => 'uri',
                        'data' => [
                            [
                                'aliased' => false,
                                'aliases' => [],
                                'deprecated' => false,
                                'namespace' => 'Foo\Bar',
                                'path' => '/foo',
                                'visible' => true
                            ],
                            [
                                'aliased' => false,
                                'aliases' => [],
                                'deprecated' => false,
                                'namespace' => 'Foo\Bar',
                                'path' => '/bar',
                                'visible' => false
                            ]
                        ]
                    ]
                ]
            ],
            'with-capabilities' => [
                'docblock' => '/**
                  * @api-label Delete a piece of content.
                  *
                  * @api-uri:private {Foo\Bar} /foo
                  *
                  * @api-contentType application/json
                  * @api-scope delete
                  * @api-capability DELETE_CONTENT
                  *
                  * @api-return:private {deleted}
                  */',
                'asserts' => [
                    'getCapabilities' => [
                        'total' => 1,
                        'annotation.name' => 'capability',
                        'data' => [
                            [
                                'capability' => 'DELETE_CONTENT'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function providerMethodsThatWillFailParsing(): array
    {
        return [
            'no-parsed-annotations' => [
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\NoAnnotationsException',
                'expected.exception.asserts' => []
            ],
            'missing-required-label-annotation' => [
                'docblock' => '/**
                  * Test throwing an exception when a required `@api-label` annotation is missing.
                  *
                  * @api-uri {Something} /some/page
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\RequiredAnnotationException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'label'
                ]
            ],
            'multiple-label-annotations' => [
                'docblock' => '/**
                  * Test throwing an exception when multiple `@api-label` annotations are present.
                  *
                  * @api-label Test method
                  * @api-label Test method
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\MultipleAnnotationsException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'label'
                ]
            ],
            'missing-required-content-type-annotation' => [
                'docblock' => '/**
                  * Test throwing an exception when a required `@api-contentType` annotation is missing.
                  *
                  * @api-label Test Method
                  * @api-uri {Something} /some/page
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\RequiredAnnotationException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'contentType'
                ]
            ],
            'missing-required-visibility-decorator' => [
                'docblock' => '/**
                  * Test throwing an exception when a required visibility decorator is missing on an annotation.
                  *
                  * @api-label Test method
                  * @api-uri {Root} /
                  * @api-contentType application/json
                  * @api-return:public {collection} \Mill\Examples\Showtimes\Representations\Representation
                  */',
                'expected.exception' => '\Mill\Exceptions\Resource\MissingVisibilityDecoratorException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'uri'
                ]
            ],
            'unsupported-decorator' => [
                'docblock' => '/**
                  * Test throwing an exception when an unsupported decorator is found.
                  *
                  * @api-label Test method
                  * @api-uri:special {Root} /
                  * @api-contentType application/json
                  * @api-return {collection} \Mill\Examples\Showtimes\Representations\Representation
                  */',
                'expected.exception' => '\Mill\Exceptions\Resource\UnsupportedDecoratorException',
                'expected.exception.asserts' => [
                    'getDecorator' => 'special',
                    'getAnnotation' => 'uri'
                ]
            ],
            'required-uri-annotation-missing' => [
                'docblock' => '/**
                  * Test throwing an exception when a required `@api-uri` annotation is missing.
                  *
                  * @api-label Test method
                  * @api-contentType application/json
                  * @api-param:public {page}
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\RequiredAnnotationException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'uri'
                ]
            ],
            'public-annotations-on-a-private-action' => [
                'docblock' => '/**
                  * Test throwing an exception when there are private annotations on a private action.
                  *
                  * @api-label Test method
                  * @api-uri:private {Search} /search
                  * @api-contentType application/json
                  * @api-scope public
                  * @api-return:private {collection} \Mill\Examples\Showtimes\Representations\Representation
                  * @api-error:public {403} \Mill\Examples\Showtimes\Representations\CodedError
                  *      (Mill\Examples\Showtimes\Representations\CodedError::DISALLOWED) If the user isn\'t allowed to
                  *      do something.
                  */',
                'expected.exception' => '\Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'error'
                ]
            ],
            'too-many-aliases' => [
                'docblock' => '/**
                  * Test throwing an exception when there are private annotations on a private action.
                  *
                  * @api-label Test method
                  * @api-uri:private:alias {Search} /search
                  * @api-uri:private:alias {Search} /search2
                  * @api-contentType application/json
                  * @api-scope public
                  * @api-return:private {collection} \Mill\Examples\Showtimes\Representations\Representation
                  * @api-error:public {403} \Mill\Examples\Showtimes\Representations\CodedError
                  *      (Mill\Examples\Showtimes\Representations\CodedError::DISALLOWED) If the user isn\'t allowed to
                  *      do something.
                  */',
                'expected.exception' => '\Mill\Exceptions\Resource\TooManyAliasedUrisException',
                'expected.exception.asserts' => []
            ]
        ];
    }
}
