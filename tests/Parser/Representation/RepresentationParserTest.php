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
     */
    public function testParseAnnotations($class, $method, $expected)
    {
        $parser = new RepresentationParser($class);
        $annotations = $parser->getAnnotations($method);

        $this->assertCount(count($expected['annotations']), $annotations);

        if (!empty($annotations)) {
            // Assert that annotations were parsed correctly as `@api-field`.
            foreach ($annotations as $name => $annotation) {
                $this->assertInstanceOf(
                    '\Mill\Parser\Annotations\FieldAnnotation',
                    $annotation,
                    sprintf('%s is not a field annotation.', $name)
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
            'rotten_tomatoes_score',
            'runtime',
            'showtimes',
            'theaters',
            'uri'
        ], array_keys($annotations));
    }

    /**
     * @dataProvider providerRepresentationsThatWillFailParsing
     */
    public function testRepresentationsThatWillFailParsing($docblock, $exception, $asserts)
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

    /**
     * @return array
     */
    public function providerParseAnnotations()
    {
        return [
            'Movie' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Movie',
                'method' => 'create',
                'expected' => [
                    'annotations' => [
                        'cast' => [
                            'capability' => false,
                            'field' => 'cast',
                            'label' => 'Cast',
                            'options' => false,
                            'subtype' => '\Mill\Examples\Showtimes\Representations\Person',
                            'type' => 'array',
                            'version' => false
                        ],
                        'content_rating' => [
                            'capability' => false,
                            'field' => 'content_rating',
                            'label' => 'MPAA rating',
                            'options' => [
                                'G',
                                'NC-17',
                                'NR',
                                'PG',
                                'PG-13',
                                'R',
                                'UR',
                                'X'
                            ],
                            'type' => 'enum',
                            'version' => false
                        ],
                        'description' => [
                            'capability' => false,
                            'field' => 'description',
                            'label' => 'Description',
                            'options' => false,
                            'type' => 'string',
                            'version' => false
                        ],
                        'director' => [
                            'capability' => false,
                            'field' => 'director',
                            'label' => 'Director',
                            'options' => false,
                            'subtype' => '\Mill\Examples\Showtimes\Representations\Person',
                            'type' => 'representation',
                            'version' => false
                        ],
                        'external_urls' => [
                            'capability' => false,
                            'field' => 'external_urls',
                            'label' => 'External URLs',
                            'options' => false,
                            'type' => 'object',
                            'version' => '>=1.1'
                        ],
                        'external_urls.imdb' => [
                            'capability' => false,
                            'field' => 'external_urls.imdb',
                            'label' => 'IMDB URL',
                            'options' => false,
                            'type' => 'string',
                            'version' => '>=1.1'
                        ],
                        'external_urls.tickets' => [
                            'capability' => 'BUY_TICKETS',
                            'field' => 'external_urls.tickets',
                            'label' => 'Tickets URL',
                            'options' => false,
                            'type' => 'string',
                            'version' => '>=1.1'
                        ],
                        'external_urls.trailer' => [
                            'capability' => false,
                            'field' => 'external_urls.trailer',
                            'label' => 'Trailer URL',
                            'options' => false,
                            'type' => 'string',
                            'version' => '>=1.1'
                        ],
                        'genres' => [
                            'capability' => false,
                            'field' => 'genres',
                            'label' => 'Genres',
                            'options' => false,
                            'subtype' => false,
                            'type' => 'array',
                            'version' => false
                        ],
                        'id' => [
                            'capability' => false,
                            'field' => 'id',
                            'label' => 'Unique ID',
                            'options' => false,
                            'type' => 'number',
                            'version' => false
                        ],
                        'kid_friendly' => [
                            'capability' => false,
                            'field' => 'kid_friendly',
                            'label' => 'Kid friendly?',
                            'options' => false,
                            'type' => 'boolean',
                            'version' => false
                        ],
                        'name' => [
                            'capability' => false,
                            'field' => 'name',
                            'label' => 'Name',
                            'options' => false,
                            'type' => 'string',
                            'version' => false
                        ],
                        'rotten_tomatoes_score' => [
                            'capability' => false,
                            'field' => 'rotten_tomatoes_score',
                            'label' => 'Rotten Tomatoes score',
                            'options' => false,
                            'type' => 'number',
                            'version' => false
                        ],
                        'runtime' => [
                            'capability' => false,
                            'field' => 'runtime',
                            'label' => 'Runtime',
                            'options' => false,
                            'type' => 'string',
                            'version' => false
                        ],
                        'showtimes' => [
                            'capability' => false,
                            'field' => 'showtimes',
                            'label' => 'Non-theater specific showtimes',
                            'options' => false,
                            'subtype' => false,
                            'type' => 'array',
                            'version' => false
                        ],
                        'theaters' => [
                            'capability' => false,
                            'field' => 'theaters',
                            'label' => 'Theaters the movie is currently showing in',
                            'options' => false,
                            'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                            'type' => 'array',
                            'version' => false
                        ],
                        'uri' => [
                            'capability' => false,
                            'field' => 'uri',
                            'label' => 'Movie URI',
                            'options' => false,
                            'type' => 'uri',
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
            'docblock-has-duplicate-capability-annotations' => [
                'docblocks' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  * @api-type uri
                  * @api-capability SomeCapability
                  * @api-capability SomeOtherCapability
                  */',
                'expected.exception' => '\Mill\Exceptions\Representation\DuplicateAnnotationsOnFieldException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'capability'
                ]
            ],
            'docblock-has-duplicate-version-annotations' => [
                'docblocks' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  * @api-type uri
                  * @api-version >3.2
                  * @api-version 3.4
                  */',
                'expected.exception' => '\Mill\Exceptions\Representation\DuplicateAnnotationsOnFieldException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'version'
                ]
            ],
            'docblock-missing-a-field' => [
                'docblocks' => '/**
                  * @api-label Canonical relative URI
                  */',
                'expected.exception' => '\Mill\Exceptions\Representation\MissingFieldAnnotationException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'field'
                ]
            ],
            'docblock-missing-a-type' => [
                'docblocks' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  */',
                'expected.exception' => '\Mill\Exceptions\Representation\MissingFieldAnnotationException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'type'
                ]
            ],
            'duplicate-fields' => [
                'docblocks' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  * @api-type uri
                  */

                 /**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  * @api-type uri
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
