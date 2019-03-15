<?php
namespace Mill\Tests\Parser\Representation;

use Mill\Exceptions\BaseException;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Representation\RepresentationParser;
use Mill\Tests\ReaderTestingTrait;
use Mill\Tests\TestCase;

class RepresentationParserTest extends TestCase
{
    use ReaderTestingTrait;

    /**
     * @dataProvider providerParseAnnotations
     * @param string $class
     * @param string $method
     * @param array $expected
     */
    public function testParseAnnotations(string $class, string $method, array $expected): void
    {
        $parser = new RepresentationParser($class, $this->getApplication());
        $annotations = $parser->getAnnotations($method);

        $this->assertCount(count($expected['annotations']), $annotations);

        if (!empty($annotations)) {
            // Assert that annotations were parsed correctly as `@api-data`.
            foreach ($annotations as $name => $annotation) {
                $this->assertInstanceOf(
                    DataAnnotation::class,
                    $annotation,
                    sprintf('%s is not a data annotation.', $name)
                );
            }

            /** @var \Mill\Parser\Annotation $annotation */
            foreach ($annotations as $name => $annotation) {
                if (!isset($expected['annotations'][$name])) {
                    $this->fail('A parsed `' . $name . '` annotation was not present in the expected data.');
                }

                $this->assertSame($expected['annotations'][$name], $annotation->toArray(), '`' . $name . '` mismatch');
            }
        }
    }

    public function testRepresentationWithUnknownAnnotations(): void
    {
        $docblock = '/**
          * @deprecated
          */';

        $this->overrideReadersWithFakeDocblockReturn($docblock);

        $annotations = (new RepresentationParser(__CLASS__, $this->getApplication()))->getAnnotations(__METHOD__);

        $this->assertEmpty($annotations);
    }

    public function testRepresentationWithApiSee(): void
    {
        $class = '\Mill\Tests\Fixtures\Representations\RepresentationWithOnlyApiSee';
        $parser = new RepresentationParser($class, $this->getApplication());
        $annotations = $parser->getAnnotations('create');

        // We're already asserting that the parser actually parses annotations, we just want to make sure that we
        // picked up the full Movie representation here by way of an `@api-see` pointer.
        $this->assertSame([
            'cast',
            'content_rating',
            'description',
            'director',
            'external_urls',
            'external_urls.imdb',
            'external_urls.tickets',
            'external_urls.trailer',
            'genres',
            'id',
            'kid_friendly',
            'name',
            'purchase.url',
            'rotten_tomatoes_score',
            'runtime',
            'showtimes',
            'theaters',
            'uri'
        ], array_keys($annotations));
    }

    /**
     * @dataProvider providerRepresentationsThatWillFailParsing
     * @param string $docblock
     * @param string $exception
     * @param array $asserts
     * @throws BaseException
     */
    public function testRepresentationsThatWillFailParsing(string $docblock, string $exception, array $asserts): void
    {
        $this->expectException($exception);
        $this->overrideReadersWithFakeDocblockReturn($docblock);

        try {
            (new RepresentationParser(__CLASS__, $this->getApplication()))->getAnnotations(__METHOD__);
        } catch (BaseException $e) {
            if ('\\' . get_class($e) !== $exception) {
                $this->fail('Unrecognized exception (' . get_class($e) . ') thrown.');
            }

            $this->assertExceptionAsserts($e, __CLASS__, __METHOD__, $asserts);
            throw $e;
        }
    }

    /**
     * @dataProvider providerRepresentationMethodsThatWillFailParsing
     * @param string $class
     * @param null|string $method
     * @param string $exception
     * @throws BaseException
     */
    public function testRepresentationMethodsThatWillFailParsing(
        string $class,
        ?string $method,
        string $exception
    ): void {
        $this->expectException($exception);

        try {
            (new RepresentationParser($class, $this->getApplication()))->getAnnotations($method);
        } catch (BaseException $e) {
            if ('\\' . get_class($e) !== $exception) {
                $this->fail('Unrecognized exception (' . get_class($e) . ') thrown.');
            }

            $this->assertExceptionAsserts($e, $class, $method);
            throw $e;
        }
    }

    public function testRepresentationThatHasVersioningAcrossMultipleAnnotations(): void
    {
        $class = '\Mill\Tests\Fixtures\Representations\RepresentationWithVersioningAcrossMultipleAnnotations';
        $parser = new RepresentationParser($class, $this->getApplication());
        $annotations = $parser->getAnnotations('create');

        $this->assertSame([
            'connections',
            'connections.things',
            'connections.things.name',
            'connections.things.uri',
            'unrelated'
        ], array_keys($annotations));

        $annotations = array_map(function (Annotation $annotation): array {
            return $annotation->toArray();
        }, $annotations);

        $this->assertEmpty($annotations['connections']['vendor_tags']);
        $this->assertSame(['tag:FEATURE_FLAG'], $annotations['connections.things']['vendor_tags']);
        $this->assertSame(['tag:MOVIE_RATINGS'], $annotations['connections.things.name']['vendor_tags']);
        $this->assertSame(['tag:FEATURE_FLAG'], $annotations['connections.things.uri']['vendor_tags']);
        $this->assertEmpty($annotations['unrelated']['vendor_tags']);

        $this->assertSame('>=3.3', $annotations['connections']['version']);
        $this->assertSame('>=3.3', $annotations['connections.things']['version']);
        $this->assertSame('>=3.3', $annotations['connections.things.name']['version']);
        $this->assertSame('3.4', $annotations['connections.things.uri']['version']);
        $this->assertEmpty($annotations['unrelated']['version']);

        $this->assertEmpty($annotations['connections']['scopes']);
        $this->assertSame('public', $annotations['connections.things']['scopes'][0]['scope']);
        $this->assertSame('public', $annotations['connections.things.name']['scopes'][0]['scope']);
        $this->assertSame('public', $annotations['connections.things.uri']['scopes'][0]['scope']);
        $this->assertEmpty($annotations['unrelated']['scopes']);
    }

    public function providerParseAnnotations(): array
    {
        return [
            'movie' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Movie',
                'method' => 'create',
                'expected' => [
                    'annotations' => [
                        'cast' => [
                            'description' => 'Cast',
                            'identifier' => 'cast',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => false,
                            'scopes' => [
                                [
                                    'description' => false,
                                    'scope' => 'public'
                                ]
                            ],
                            'subtype' => '\Mill\Examples\Showtimes\Representations\Person',
                            'type' => 'array',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'content_rating' => [
                            'description' => 'MPAA rating',
                            'identifier' => 'content_rating',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => 'NR',
                            'scopes' => [],
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
                            'version' => false
                        ],
                        'description' => [
                            'description' => 'Description',
                            'identifier' => 'description',
                            'nullable' => true,
                            'required' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'director' => [
                            'description' => 'Director',
                            'identifier' => 'director',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => false,
                            'scopes' => [
                                [
                                    'description' => false,
                                    'scope' => 'public'
                                ]
                            ],
                            'subtype' => false,
                            'type' => '\Mill\Examples\Showtimes\Representations\Person',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'external_urls' => [
                            'description' => 'External URLs',
                            'identifier' => 'external_urls',
                            'nullable' => false,
                            'required' => false,
                            'sample_data' => false,
                            'scopes' => [
                                [
                                    'description' => false,
                                    'scope' => 'public'
                                ]
                            ],
                            'subtype' => 'object',
                            'type' => 'array',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => '>=1.1'
                        ],
                        'external_urls.imdb' => [
                            'description' => 'IMDB URL',
                            'identifier' => 'external_urls.imdb',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => 'https://www.imdb.com/title/tt0089013/',
                            'scopes' => [
                                [
                                    'description' => false,
                                    'scope' => 'public'
                                ]
                            ],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => '>=1.1'
                        ],
                        'external_urls.tickets' => [
                            'description' => 'Tickets URL',
                            'identifier' => 'external_urls.tickets',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => false,
                            'scopes' => [
                                [
                                    'description' => false,
                                    'scope' => 'public'
                                ]
                            ],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => [],
                            'vendor_tags' => [
                                'tag:BUY_TICKETS'
                            ],
                            'version' => '>=1.1 <1.1.3'
                        ],
                        'external_urls.trailer' => [
                            'description' => 'Trailer URL',
                            'identifier' => 'external_urls.trailer',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => false,
                            'scopes' => [
                                [
                                    'description' => false,
                                    'scope' => 'public'
                                ]
                            ],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => '>=1.1'
                        ],
                        'genres' => [
                            'description' => 'Genres',
                            'identifier' => 'genres',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => 'uri',
                            'type' => 'array',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'id' => [
                            'description' => 'Unique ID',
                            'identifier' => 'id',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => '1234',
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'number',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'kid_friendly' => [
                            'description' => 'Kid friendly?',
                            'identifier' => 'kid_friendly',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => 'false',
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'boolean',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'name' => [
                            'description' => 'Name',
                            'identifier' => 'name',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => 'Demons',
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'purchase.url' => [
                            'description' => 'URL to purchase the film.',
                            'identifier' => 'purchase.url',
                            'nullable' => true,
                            'required' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'rotten_tomatoes_score' => [
                            'description' => 'Rotten Tomatoes score',
                            'identifier' => 'rotten_tomatoes_score',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => '56',
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'number',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'runtime' => [
                            'description' => 'Runtime',
                            'identifier' => 'runtime',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => '1hr 20min',
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'showtimes' => [
                            'description' => 'Non-theater specific showtimes',
                            'identifier' => 'showtimes',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => 'string',
                            'type' => 'array',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'theaters' => [
                            'description' => 'Theaters the movie is currently showing in',
                            'identifier' => 'theaters',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                            'type' => 'array',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'uri' => [
                            'description' => 'Movie URI',
                            'identifier' => 'uri',
                            'nullable' => false,
                            'required' => true,
                            'sample_data' => '/movies/1234',
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'uri',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ]
                    ]
                ]
            ]
        ];
    }

    public function providerRepresentationsThatWillFailParsing(): array
    {
        return [
            'docblock-unparseable-mson' => [
                'docblocks' => '/**
                  * @api-data this is not parseable mson
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\InvalidMSONSyntaxException',
                'expected.exception.asserts' => []
            ],
            'duplicate-fields' => [
                'docblocks' => '/**
                  * @api-data uri (uri) - Canonical relative URI
                  */

                 /**
                  * @api-data uri (uri) - Canonical relative URI
                  */',
                'expected.exception' => '\Mill\Exceptions\Representation\DuplicateFieldException',
                'expected.exception.asserts' => [
                    'getField' => 'uri'
                ]
            ]
        ];
    }

    public function providerRepresentationMethodsThatWillFailParsing(): array
    {
        return [
            'no-method-supplied' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Movie',
                'method' => null,
                'expected.exception' => '\Mill\Exceptions\MethodNotSuppliedException',
            ],
            'method-that-doesnt-exist' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Movie',
                'method' => 'invalid_method',
                'expected.exception' => '\Mill\Exceptions\MethodNotImplementedException',
            ]
        ];
    }
}
