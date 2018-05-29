<?php
namespace Mill\Tests\Parser\Resource\Action;

use Mill\Exceptions\BaseException;
use Mill\Parser\Annotations\MaxVersionAnnotation;
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

        $this->assertCount($expected['vendor_tags.total'], $parser->getVendorTags());

        /** @var \Mill\Parser\Annotations\MinVersionAnnotation $min_version */
        $min_version = $parser->getMinimumVersion();
        if ($expected['minimum_version']) {
            $this->assertInstanceOf(MinVersionAnnotation::class, $min_version);
            $this->assertSame($expected['minimum_version'], $min_version->getMinimumVersion());
        } else {
            $this->assertNull($min_version);
        }

        /** @var \Mill\Parser\Annotations\MaxVersionAnnotation $max_version */
        $max_version = $parser->getMaximumVersion();
        if ($expected['maximum_version']) {
            $this->assertInstanceOf(MaxVersionAnnotation::class, $max_version);
            $this->assertSame($expected['maximum_version'], $max_version->getMaximumVersion());
        } else {
            $this->assertNull($max_version);
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
        $this->assertSame($expected['path'], $parser->getPath()->getPath());

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

        // Verify exploded parameter dot notation.
        $exploded_content = $parser->getExplodedParameterDotNotation();
        foreach ($exploded_content as $annotation => $data) {
            $this->assertSame($expected['params.exploded'][$annotation], $data, '`' . $annotation . '` mismatch');
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
                    'group' => 'Movies',
                    'vendor_tags.total' => 0,
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
                    'path' => '/movies/+id',
                    'minimum_version' => false,
                    'maximum_version' => false,
                    'responses.length' => 5,
                    'annotations' => [
                        'error' => [
                            [
                                'description' => 'If the movie could not be found.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'description' => 'For no reason.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'vendor_tags' => [],
                                'version' => '>=1.1.3',
                                'visible' => true
                            ],
                            [
                                'description' => 'For some other reason.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'vendor_tags' => [],
                                'version' => '>=1.1.3',
                                'visible' => true
                            ]
                        ],
                        'path' => [
                            [
                                'aliased' => true,
                                'aliases' => [],
                                'deprecated' => false,
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
                                        'path' => '/movie/+id',
                                        'visible' => false
                                    ]
                                ],
                                'deprecated' => false,
                                'path' => '/movies/+id',
                                'visible' => true
                            ]
                        ],
                        'pathparam' => [
                            [
                                'description' => 'Movie ID',
                                'field' => 'id',
                                'type' => 'integer',
                                'values' => []
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
                    ],
                    'params.exploded' => []
                ]
            ],
            'PATCH' => [
                'method' => 'PATCH',
                'expected' => [
                    'label' => 'Update a movie.',
                    'description' => 'Update a movies data.',
                    'group' => 'Movies',
                    'vendor_tags.total' => 0,
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
                    'path' => '/movies/+id',
                    'minimum_version' => '1.1',
                    'maximum_version' => false,
                    'responses.length' => 8,
                    'path.aliases' => [],
                    'annotations' => [
                        'error' => [
                            [
                                'description' => 'If there is a problem with the request.',
                                'error_code' => false,
                                'http_code' => '400 Bad Request',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'description' => 'If the IMDB URL could not be validated.',
                                'error_code' => false,
                                'http_code' => '400 Bad Request',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'description' => 'If the movie could not be found.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            [
                                'description' => 'If the trailer URL could not be validated.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'vendor_tags' => [],
                                'version' => '>=1.1.3',
                                'visible' => true
                            ],
                            [
                                'description' => 'If something cool happened.',
                                'error_code' => '1337',
                                'http_code' => '403 Forbidden',
                                'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                                'vendor_tags' => [],
                                'version' => '>=1.1.3',
                                'visible' => false
                            ],
                            [
                                'description' => 'If the user is not allowed to edit that movie.',
                                'error_code' => '666',
                                'http_code' => '403 Forbidden',
                                'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                                'vendor_tags' => [],
                                'version' => '>=1.1.3',
                                'visible' => true
                            ]
                        ],
                        'minversion' => [
                            [
                                'minimum_version' => '1.1'
                            ]
                        ],
                        'param' => [
                            'cast' => [
                                'deprecated' => false,
                                'description' => 'Array of cast members.',
                                'field' => 'cast',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => 'object',
                                'type' => 'array',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'cast.name' => [
                                'deprecated' => false,
                                'description' => 'Cast member name.',
                                'field' => 'cast.name',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'cast.role' => [
                                'deprecated' => false,
                                'description' => 'Cast member role.',
                                'field' => 'cast.role',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'content_rating' => [
                                'deprecated' => false,
                                'description' => 'MPAA rating',
                                'field' => 'content_rating',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
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
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'description' => [
                                'deprecated' => false,
                                'description' => 'Description, or tagline, for the movie.',
                                'field' => 'description',
                                'nullable' => false,
                                'required' => true,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'director' => [
                                'deprecated' => false,
                                'description' => 'Name of the director.',
                                'field' => 'director',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'is_kid_friendly' => [
                                'deprecated' => false,
                                'description' => 'Is this movie kid friendly?',
                                'field' => 'is_kid_friendly',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'boolean',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'name' => [
                                'deprecated' => false,
                                'description' => 'Name of the movie.',
                                'field' => 'name',
                                'nullable' => false,
                                'required' => true,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'genres' => [
                                'deprecated' => false,
                                'description' => 'Array of movie genres.',
                                'field' => 'genres',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'array',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'imdb' => [
                                'deprecated' => false,
                                'description' => 'IMDB URL',
                                'field' => 'imdb',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => '>=1.1.1',
                                'visible' => true
                            ],
                            'rotten_tomatoes_score' => [
                                'deprecated' => false,
                                'description' => 'Rotten Tomatoes score',
                                'field' => 'rotten_tomatoes_score',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'integer',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'runtime' => [
                                'deprecated' => false,
                                'description' => 'Movie runtime, in `HHhr MMmin` format.',
                                'field' => 'runtime',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'trailer' => [
                                'deprecated' => false,
                                'description' => 'Trailer URL',
                                'field' => 'trailer',
                                'nullable' => true,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'path' => [
                            [
                                'aliased' => false,
                                'aliases' => [],
                                'deprecated' => false,
                                'path' => '/movies/+id',
                                'visible' => true
                            ]
                        ],
                        'pathparam' => [
                            [
                                'description' => 'Movie ID',
                                'field' => 'id',
                                'type' => 'integer',
                                'values' => []
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
                    ],
                    'params.exploded' => [
                        'cast' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Array of cast members.',
                                'field' => 'cast',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => 'object',
                                'type' => 'array',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ],
                            'name' => [
                                '__NESTED_DATA__' => [
                                    'deprecated' => false,
                                    'description' => 'Cast member name.',
                                    'field' => 'cast.name',
                                    'nullable' => false,
                                    'required' => false,
                                    'sample_data' => false,
                                    'subtype' => false,
                                    'type' => 'string',
                                    'values' => [],
                                    'vendor_tags' => [],
                                    'version' => false,
                                    'visible' => true
                                ]
                            ],
                            'role' => [
                                '__NESTED_DATA__' => [
                                    'deprecated' => false,
                                    'description' => 'Cast member role.',
                                    'field' => 'cast.role',
                                    'nullable' => false,
                                    'required' => false,
                                    'sample_data' => false,
                                    'subtype' => false,
                                    'type' => 'string',
                                    'values' => [],
                                    'vendor_tags' => [],
                                    'version' => false,
                                    'visible' => true
                                ]
                            ],
                        ],
                        'content_rating' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'MPAA rating',
                                'field' => 'content_rating',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
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
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'description' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Description, or tagline, for the movie.',
                                'field' => 'description',
                                'nullable' => false,
                                'required' => true,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'director' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Name of the director.',
                                'field' => 'director',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'is_kid_friendly' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Is this movie kid friendly?',
                                'field' => 'is_kid_friendly',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'boolean',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'name' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Name of the movie.',
                                'field' => 'name',
                                'nullable' => false,
                                'required' => true,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'genres' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Array of movie genres.',
                                'field' => 'genres',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'array',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'imdb' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'IMDB URL',
                                'field' => 'imdb',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => '>=1.1.1',
                                'visible' => true
                            ]
                        ],
                        'rotten_tomatoes_score' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Rotten Tomatoes score',
                                'field' => 'rotten_tomatoes_score',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'integer',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'runtime' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Movie runtime, in `HHhr MMmin` format.',
                                'field' => 'runtime',
                                'nullable' => false,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ],
                        'trailer' => [
                            '__NESTED_DATA__' => [
                                'deprecated' => false,
                                'description' => 'Trailer URL',
                                'field' => 'trailer',
                                'nullable' => true,
                                'required' => false,
                                'sample_data' => false,
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => true
                            ]
                        ]
                    ],
                ]
            ],
            'DELETE' => [
                'method' => 'DELETE',
                'expected' => [
                    'label' => 'Delete a movie.',
                    'description' => 'Delete a movie.',
                    'group' => 'Movies',
                    'vendor_tags.total' => 1,
                    'content_types.latest-version' => null,
                    'content_types' => [
                        [
                            'content_type' => 'application/json',
                            'version' => false
                        ]
                    ],
                    'path' => '/movies/+id',
                    'minimum_version' => '1.1',
                    'maximum_version' => '1.1.2',
                    'responses.length' => 2,
                    'path.aliases' => [],
                    'annotations' => [
                        'error' => [
                            [
                                'description' => 'If the movie could not be found.',
                                'error_code' => false,
                                'http_code' => '404 Not Found',
                                'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                                'vendor_tags' => [],
                                'version' => false,
                                'visible' => false
                            ]
                        ],
                        'maxversion' => [
                            [
                                'maximum_version' => '1.1.2'
                            ]
                        ],
                        'minversion' => [
                            [
                                'minimum_version' => '1.1'
                            ]
                        ],
                        'path' => [
                            [
                                'aliased' => false,
                                'aliases' => [],
                                'deprecated' => false,
                                'path' => '/movies/+id',
                                'visible' => false
                            ]
                        ],
                        'pathparam' => [
                            [
                                'description' => 'Movie ID',
                                'field' => 'id',
                                'type' => 'integer',
                                'values' => []
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
                        ],
                        'vendortag' => [
                            [
                                'vendor_tag' => 'tag:DELETE_CONTENT'
                            ]
                        ]
                    ],
                    'params.exploded' => []
                ]
            ]
        ];
    }

    public function providerParsingOfSpecificUseCases(): array
    {
        return [
            'with-aliased-paths' => [
                'docblock' => '/**
                  * @api-label Update a piece of content.
                  * @api-group Foo\Bar
                  *
                  * @api-path:public /foo
                  * @api-path:private:alias /bar
                  *
                  * @api-contenttype application/json
                  * @api-scope public
                  *
                  * @api-return:public {ok}
                  */',
                'asserts' => [
                    'getPaths' => [
                        'total' => 2,
                        'annotation.name' => 'path',
                        'data' => [
                            [
                                'aliased' => false,
                                'aliases' => [
                                    [
                                        'aliased' => true,
                                        'aliases' => [],
                                        'deprecated' => false,
                                        'path' => '/bar',
                                        'visible' => false
                                    ]
                                ],
                                'deprecated' => false,
                                'path' => '/foo',
                                'visible' => true
                            ],
                            [
                                'aliased' => true,
                                'aliases' => [],
                                'deprecated' => false,
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
                  * @api-group Foo\Bar
                  *
                  * @api-path:public /foo
                  * @api-path:private /bar
                  *
                  * @api-contenttype application/json
                  * @api-scope public
                  *
                  * @api-return:public {ok}
                  */',
                'asserts' => [
                    'getPaths' => [
                        'total' => 2,
                        'annotation.name' => 'path',
                        'data' => [
                            [
                                'aliased' => false,
                                'aliases' => [],
                                'deprecated' => false,
                                'path' => '/foo',
                                'visible' => true
                            ],
                            [
                                'aliased' => false,
                                'aliases' => [],
                                'deprecated' => false,
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
                  * @api-group Foo\Bar
                  *
                  * @api-path:private /foo
                  *
                  * @api-contenttype application/json
                  * @api-scope delete
                  * @api-vendortag tag:DELETE_CONTENT
                  *
                  * @api-return:private {deleted}
                  */',
                'asserts' => [
                    'getVendorTags' => [
                        'total' => 1,
                        'annotation.name' => 'vendortag',
                        'data' => [
                            [
                                'vendor_tag' => 'tag:DELETE_CONTENT'
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
                  * @api-path /some/page
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
                  * Test throwing an exception when a required `@api-contenttype` annotation is missing.
                  *
                  * @api-label Test Method
                  * @api-group Something
                  * @api-path /some/page
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\RequiredAnnotationException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'contenttype'
                ]
            ],
            'missing-required-visibility-decorator' => [
                'docblock' => '/**
                  * Test throwing an exception when a required visibility decorator is missing on an annotation.
                  *
                  * @api-label Test method
                  * @api-group Root
                  * @api-path /
                  * @api-contenttype application/json
                  * @api-return:public {collection} \Mill\Examples\Showtimes\Representations\Representation
                  */',
                'expected.exception' => '\Mill\Exceptions\Resource\MissingVisibilityDecoratorException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'path'
                ]
            ],
            'unsupported-decorator' => [
                'docblock' => '/**
                  * Test throwing an exception when an unsupported decorator is found.
                  *
                  * @api-label Test method
                  * @api-group Root
                  * @api-path:special /
                  * @api-contenttype application/json
                  * @api-return {collection} \Mill\Examples\Showtimes\Representations\Representation
                  */',
                'expected.exception' => '\Mill\Exceptions\Resource\UnsupportedDecoratorException',
                'expected.exception.asserts' => [
                    'getDecorator' => 'special',
                    'getAnnotation' => 'path'
                ]
            ],
            'required-path-annotation-missing' => [
                'docblock' => '/**
                  * Test throwing an exception when a required `@api-path` annotation is missing.
                  *
                  * @api-label Test method
                  * @api-group Something
                  * @api-contenttype application/json
                  * @api-param:public {page}
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\RequiredAnnotationException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'path'
                ]
            ],
            'public-annotations-on-a-private-action' => [
                'docblock' => '/**
                  * Test throwing an exception when there are private annotations on a private action.
                  *
                  * @api-label Test method
                  * @api-group Search
                  * @api-path:private /search
                  * @api-contenttype application/json
                  * @api-scope public
                  * @api-return:private {collection} \Mill\Examples\Showtimes\Representations\Representation
                  * @api-error:public 403 (\Mill\Examples\Showtimes\Representations\CodedError<666>) - If the user
                  *     isn\'t allowed to do something.
                  */',
                'expected.exception' => '\Mill\Exceptions\Resource\PublicDecoratorOnPrivateActionException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'error'
                ]
            ],
            'too-many-aliases' => [
                'docblock' => '/**
                  * Test throwing an exception when there is no canonical path and only path aliases.
                  *
                  * @api-label Test method
                  * @api-group Search
                  * @api-path:private:alias /search
                  * @api-path:private:alias /search2
                  * @api-contenttype application/json
                  * @api-scope public
                  * @api-return:private {collection} \Mill\Examples\Showtimes\Representations\Representation
                  * @api-error:public 403 (\Mill\Examples\Showtimes\Representations\CodedError<666>) - If the user
                  *     isn\'t allowed to do something.
                  */',
                'expected.exception' => '\Mill\Exceptions\Resource\TooManyAliasedPathsException',
                'expected.exception.asserts' => []
            ]
        ];
    }
}
