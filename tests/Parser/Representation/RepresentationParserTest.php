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

    /**
     * @dataProvider providerRepresentationsThatWillFailParsing
     */
    public function testRepresentationsThatWillFailParsing($docblock, $exception, $regex)
    {
        $this->expectException($exception);
        foreach ($regex as $rule) {
            $this->expectExceptionMessageRegExp($rule);
        }

        $this->overrideReadersWithFakeDocblockReturn($docblock);

        (new RepresentationParser(__CLASS__))->getAnnotations(__METHOD__);
    }

    /**
     * @dataProvider providerRepresentationMethodsThatWillFailParsing
     */
    public function testRepresentationMethodsThatWillFailParsing($class, $method, $exception, $regex)
    {
        $this->expectException($exception);
        foreach ($regex as $rule) {
            $this->expectExceptionMessageRegExp($rule);
        }

        (new RepresentationParser($class))->getAnnotations($method);
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
                            'subtype' => false,
                            'type' => 'array',
                            'version' => false
                        ],
                        'content_rating' => [
                            'capability' => false,
                            'field' => 'content_rating',
                            'label' => 'MPAA rating',
                            'options' => [
                                'G',
                                'PG',
                                'PG-13',
                                'R',
                                'NC-17',
                                'X',
                                'NR',
                                'UR'
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
                            'type' => 'string',
                            'version' => false
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
                        'name' => [
                            'capability' => false,
                            'field' => 'name',
                            'label' => 'Name',
                            'options' => false,
                            'type' => 'string',
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
                        'urls' => [
                            'capability' => 'NONE',
                            'field' => 'urls',
                            'label' => 'External URLs',
                            'options' => false,
                            'type' => 'object',
                            'version' => false
                        ],
                        'urls.imdb' => [
                            'capability' => false,
                            'field' => 'urls.imdb',
                            'label' => 'IMDB URL',
                            'options' => false,
                            'type' => 'string',
                            'version' => false
                        ],
                        'urls.tickets' => [
                            'capability' => false,
                            'field' => 'urls.tickets',
                            'label' => 'Tickets URL',
                            'options' => false,
                            'type' => 'string',
                            'version' => false
                        ],
                        'urls.trailer' => [
                            'capability' => false,
                            'field' => 'urls.trailer',
                            'label' => 'Trailer URL',
                            'options' => false,
                            'type' => 'string',
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
                'expected.exception.regex' => [
                    '/api-capability/'
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
                'expected.exception.regex' => [
                    '/api-version/'
                ]
            ],
            'docblock-missing-a-field' => [
                'docblocks' => '/**
                  * @api-label Canonical relative URI
                  */',
                'expected.exception' => '\Mill\Exceptions\Representation\MissingFieldAnnotationException',
                'expected.exception.regex' => [
                    '/api-field/'
                ]
            ],
            'docblock-missing-a-type' => [
                'docblocks' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  */',
                'expected.exception' => '\Mill\Exceptions\Representation\MissingFieldAnnotationException',
                'expected.exception.regex' => [
                    '/api-type/'
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
                'expected.exception.regex' => [
                    '/`uri`/'
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
                'expected.exception.regex' => []
            ],
            'method-that-doesnt-exist' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Movie',
                'method' => 'invalid_method',
                'expected.exception' => '\Mill\Exceptions\MethodNotImplementedException',
                'expected.exception.regex' => [
                    '/invalid_method/'
                ]
            ]
        ];
    }
}
