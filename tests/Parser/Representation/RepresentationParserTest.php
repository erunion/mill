<?php
namespace Mill\Tests\Parser\Representation;

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
     * @return void
     */
    public function testParseAnnotations($class, $method, array $expected)
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

    public function testRepresentationWithUnknownAnnotations()
    {
        $docblock = '/**
          * @deprecated
          */';

        $this->overrideReadersWithFakeDocblockReturn($docblock);

        $annotations = (new RepresentationParser(__CLASS__))->getAnnotations(__METHOD__);

        $this->assertEmpty($annotations);
    }

    public function testRepresentationWithApiSee()
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
     * @throws \Exception
     * @return void
     */
    public function testRepresentationsThatWillFailParsing($docblock, $exception, array $asserts)
    {
        $this->expectException($exception);
        $this->overrideReadersWithFakeDocblockReturn($docblock);

        try {
            (new RepresentationParser(__CLASS__))->getAnnotations(__METHOD__);
        } catch (\Exception $e) {
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
     * @param string $method
     * @param string $exception
     * @throws \Exception
     * @return void
     */
    public function testRepresentationMethodsThatWillFailParsing($class, $method, $exception)
    {
        $this->expectException($exception);

        try {
            (new RepresentationParser($class))->getAnnotations($method);
        } catch (\Exception $e) {
            if ('\\' . get_class($e) !== $exception) {
                $this->fail('Unrecognized exception (' . get_class($e) . ') thrown.');
            }

            $this->assertExceptionAsserts($e, $class, $method);
            throw $e;
        }
    }

    public function testRepresentationThatHasVersioningAcrossMultipleAnnotations()
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

        $this->assertSame('>=3.3', $annotations['connections']->toArray()['version']);
        $this->assertSame('>=3.3', $annotations['connections.things']->toArray()['version']);
        $this->assertSame('>=3.3', $annotations['connections.things.name']->toArray()['version']);
        $this->assertSame('3.4', $annotations['connections.things.uri']->toArray()['version']);
        $this->assertEmpty($annotations['unrelated']->toArray()['version']);

        $this->assertEmpty($annotations['connections']->toArray()['capability']);
        $this->assertSame('NONE', $annotations['connections.things']->toArray()['capability']);
        $this->assertSame('MOVIE_RATINGS', $annotations['connections.things.name']->toArray()['capability']);
        $this->assertSame('NONE', $annotations['connections.things.uri']->toArray()['capability']);
        $this->assertEmpty($annotations['unrelated']->toArray()['version']);
    }

    /**
     * @return array
     */
    public function providerParseAnnotations()
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
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'string',
                            'values' => false,
                            'version' => '>=1.1'
                        ],
                        'external_urls.trailer' => [
                            'capability' => false,
                            'description' => 'Trailer URL',
                            'identifier' => 'external_urls.trailer',
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

    /**
     * @return array
     */
    public function providerRepresentationsThatWillFailParsing()
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

    /**
     * @return array
     */
    public function providerRepresentationMethodsThatWillFailParsing()
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
