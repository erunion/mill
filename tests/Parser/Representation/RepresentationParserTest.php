<?php
namespace Mill\Tests\Parser\Representation;

use Mill\Exceptions\BaseException;
use Mill\Parser\Annotation;
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
        $parser = new RepresentationParser($class);
        $annotations = $parser->getAnnotations($method);

        $this->assertCount(count($expected['annotations']), $annotations);

        if (!empty($annotations)) {
            // Assert that annotations were parsed correctly as `@api-data`.
            foreach ($annotations as $name => $annotation) {
                $this->assertInstanceOf(
                    '\Mill\Parser\Annotations\DataAnnotation',
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

        $annotations = (new RepresentationParser(__CLASS__))->getAnnotations(__METHOD__);

        $this->assertEmpty($annotations);
    }

    public function testRepresentationWithApiSee(): void
    {
        $parser = new RepresentationParser('\Mill\Tests\Fixtures\Representations\RepresentationWithOnlyApiSee');
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
            (new RepresentationParser(__CLASS__))->getAnnotations(__METHOD__);
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
            (new RepresentationParser($class))->getAnnotations($method);
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
        $parser = new RepresentationParser($class);
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

        $this->assertEmpty($annotations['connections']['capability']);
        $this->assertSame('FEATURE_FLAG', $annotations['connections.things']['capability']);
        $this->assertSame('MOVIE_RATINGS', $annotations['connections.things.name']['capability']);
        $this->assertSame('FEATURE_FLAG', $annotations['connections.things.uri']['capability']);
        $this->assertEmpty($annotations['unrelated']['capability']);

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
                            'capability' => false,
                            'description' => 'Cast',
                            'identifier' => 'cast',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [
                                [
                                    'description' => false,
                                    'scope' => 'public'
                                ]
                            ],
                            'subtype' => '\Mill\Examples\Showtimes\Representations\Person',
                            'type' => 'array',
                            'values' => false,
                            'version' => false
                        ],
                        'content_rating' => [
                            'capability' => false,
                            'description' => 'MPAA rating',
                            'identifier' => 'content_rating',
                            'nullable' => false,
                            'sample_data' => 'G',
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
                            'version' => false
                        ],
                        'description' => [
                            'capability' => false,
                            'description' => 'Description',
                            'identifier' => 'description',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => false,
                            'version' => false
                        ],
                        'director' => [
                            'capability' => false,
                            'description' => 'Director',
                            'identifier' => 'director',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [
                                [
                                    'description' => false,
                                    'scope' => 'public'
                                ]
                            ],
                            'subtype' => false,
                            'type' => '\Mill\Examples\Showtimes\Representations\Person',
                            'values' => false,
                            'version' => false
                        ],
                        'external_urls' => [
                            'capability' => false,
                            'description' => 'External URLs',
                            'identifier' => 'external_urls',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'object',
                            'values' => false,
                            'version' => '>=1.1'
                        ],
                        'external_urls.imdb' => [
                            'capability' => false,
                            'description' => 'IMDB URL',
                            'identifier' => 'external_urls.imdb',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => false,
                            'version' => '>=1.1'
                        ],
                        'external_urls.tickets' => [
                            'capability' => 'BUY_TICKETS',
                            'description' => 'Tickets URL',
                            'identifier' => 'external_urls.tickets',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => false,
                            'version' => '>=1.1 <1.1.3'
                        ],
                        'external_urls.trailer' => [
                            'capability' => false,
                            'description' => 'Trailer URL',
                            'identifier' => 'external_urls.trailer',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => false,
                            'version' => '>=1.1'
                        ],
                        'genres' => [
                            'capability' => false,
                            'description' => 'Genres',
                            'identifier' => 'genres',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'array',
                            'values' => false,
                            'version' => false
                        ],
                        'id' => [
                            'capability' => false,
                            'description' => 'Unique ID',
                            'identifier' => 'id',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'number',
                            'values' => false,
                            'version' => false
                        ],
                        'kid_friendly' => [
                            'capability' => false,
                            'description' => 'Kid friendly?',
                            'identifier' => 'kid_friendly',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'boolean',
                            'values' => false,
                            'version' => false
                        ],
                        'name' => [
                            'capability' => false,
                            'description' => 'Name',
                            'identifier' => 'name',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => false,
                            'version' => false
                        ],
                        'purchase.url' => [
                            'capability' => false,
                            'description' => 'URL to purchase the film.',
                            'identifier' => 'purchase.url',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => false,
                            'version' => false
                        ],
                        'rotten_tomatoes_score' => [
                            'capability' => false,
                            'description' => 'Rotten Tomatoes score',
                            'identifier' => 'rotten_tomatoes_score',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'number',
                            'values' => false,
                            'version' => false
                        ],
                        'runtime' => [
                            'capability' => false,
                            'description' => 'Runtime',
                            'identifier' => 'runtime',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => false,
                            'version' => false
                        ],
                        'showtimes' => [
                            'capability' => false,
                            'description' => 'Non-theater specific showtimes',
                            'identifier' => 'showtimes',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'array',
                            'values' => false,
                            'version' => false
                        ],
                        'theaters' => [
                            'capability' => false,
                            'description' => 'Theaters the movie is currently showing in',
                            'identifier' => 'theaters',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                            'type' => 'array',
                            'values' => false,
                            'version' => false
                        ],
                        'uri' => [
                            'capability' => false,
                            'description' => 'Movie URI',
                            'identifier' => 'uri',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'uri',
                            'values' => false,
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
