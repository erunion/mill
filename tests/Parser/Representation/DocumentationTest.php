<?php
namespace Mill\Tests\Parser\Representation;

use Mill\Parser\Representation\Documentation;
use Mill\Tests\TestCase;

class DocumentationTest extends TestCase
{
    /**
     * @dataProvider providerParseDocumentationReturnsRepresentation
     */
    public function testParseDocumentationReturnsRepresentation($class, $method, $expected)
    {
        $parsed = (new Documentation($class, $method))->parse();
        $representation = $parsed->toArray();

        $this->assertSame($class, $parsed->getClass());
        $this->assertSame($method, $parsed->getMethod());

        $this->assertSame($expected['label'], $parsed->getLabel());
        $this->assertSame($expected['label'], $representation['label']);
        $this->assertSame($expected['description.length'], strlen($representation['description']));

        // Verify content dot notation.
        $this->assertSame(array_keys($expected['content']), array_keys($representation['content']));
        $this->assertCount(count($expected['content']), $representation['content']);
        foreach ($representation['content'] as $annotation => $data) {
            $this->assertSame($expected['content'][$annotation], $data);
        }

        // Verify exploded content dot notation.
        $exploded_content = $parsed->getExplodedContentDotNotation();
        foreach ($exploded_content as $annotation => $data) {
            $this->assertSame($expected['content.exploded'][$annotation], $data);
        }
    }

    /**
     * @dataProvider providerParseDocumentationFailsOnBadRepresentations
     */
    public function testParseDocumentationFailsOnBadRepresentations($class, $method, $exception)
    {
        $this->expectException($exception);

        (new Documentation($class, $method))->parse();
    }

    /**
     * @return array
     */
    public function providerParseDocumentationReturnsRepresentation()
    {
        return [
            'Movie' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Movie',
                'method' => 'create',
                'expected' => [
                    'label' => 'Movie',
                    'description.length' => 41,
                    'content' => [
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
                    ],
                    'content.exploded' => [
                        'cast' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'cast',
                                'label' => 'Cast',
                                'options' => false,
                                'subtype' => '\Mill\Examples\Showtimes\Representations\Person',
                                'type' => 'array',
                                'version' => false
                            ]
                        ],
                        'content_rating' => [
                            '__FIELD_DATA__' => [
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
                            ]
                        ],
                        'description' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'description',
                                'label' => 'Description',
                                'options' => false,
                                'type' => 'string',
                                'version' => false
                            ]
                        ],
                        'director' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'director',
                                'label' => 'Director',
                                'options' => false,
                                'subtype' => '\Mill\Examples\Showtimes\Representations\Person',
                                'type' => 'representation',
                                'version' => false
                            ]
                        ],
                        'external_urls' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'external_urls',
                                'label' => 'External URLs',
                                'options' => false,
                                'type' => 'object',
                                'version' => '>=1.1'
                            ],
                            'imdb' => [
                                '__FIELD_DATA__' => [
                                    'capability' => false,
                                    'field' => 'external_urls.imdb',
                                    'label' => 'IMDB URL',
                                    'options' => false,
                                    'type' => 'string',
                                    'version' => '>=1.1'
                                ]
                            ],
                            'tickets' => [
                                '__FIELD_DATA__' => [
                                    'capability' => 'BUY_TICKETS',
                                    'field' => 'external_urls.tickets',
                                    'label' => 'Tickets URL',
                                    'options' => false,
                                    'type' => 'string',
                                    'version' => '>=1.1'
                                ]
                            ],
                            'trailer' => [
                                '__FIELD_DATA__' => [
                                    'capability' => false,
                                    'field' => 'external_urls.trailer',
                                    'label' => 'Trailer URL',
                                    'options' => false,
                                    'type' => 'string',
                                    'version' => '>=1.1'
                                ]
                            ]
                        ],
                        'genres' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'genres',
                                'label' => 'Genres',
                                'options' => false,
                                'subtype' => false,
                                'type' => 'array',
                                'version' => false
                            ]
                        ],
                        'id' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'id',
                                'label' => 'Unique ID',
                                'options' => false,
                                'type' => 'number',
                                'version' => false
                            ]
                        ],
                        'kid_friendly' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'kid_friendly',
                                'label' => 'Kid friendly?',
                                'options' => false,
                                'type' => 'boolean',
                                'version' => false
                            ]
                        ],
                        'name' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'name',
                                'label' => 'Name',
                                'options' => false,
                                'type' => 'string',
                                'version' => false
                            ]
                        ],
                        'rotten_tomatoes_score' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'rotten_tomatoes_score',
                                'label' => 'Rotten Tomatoes score',
                                'options' => false,
                                'type' => 'number',
                                'version' => false
                            ]
                        ],
                        'runtime' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'runtime',
                                'label' => 'Runtime',
                                'options' => false,
                                'type' => 'string',
                                'version' => false
                            ]
                        ],
                        'showtimes' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'showtimes',
                                'label' => 'Non-theater specific showtimes',
                                'options' => false,
                                'subtype' => false,
                                'type' => 'array',
                                'version' => false
                            ]
                        ],
                        'theaters' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'theaters',
                                'label' => 'Theaters the movie is currently showing in',
                                'options' => false,
                                'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                                'type' => 'array',
                                'version' => false
                            ]
                        ],
                        'uri' => [
                            '__FIELD_DATA__' => [
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
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerParseDocumentationFailsOnBadRepresentations()
    {
        return [
            'no-annotations' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithNoAnnotations',
                'method' => 'create',
                'expected.exception' => '\Mill\Exceptions\Resource\NoAnnotationsException'
            ],
            'no-annotations-on-the-class' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithNoClassAnnotations',
                'method' => 'create',
                'expected.exception' => '\Mill\Exceptions\Resource\NoAnnotationsException'
            ],
            'missing-a-required-label-annotation' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithRequiredLabelAnnotationMissing',
                'method' => 'create',
                'expected.exception' => '\Mill\Exceptions\RequiredAnnotationException'
            ],
            'multiple-label-annotations' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithMultipleLabelAnnotations',
                'method' => 'create',
                'expected.exception' => '\Mill\Exceptions\MultipleAnnotationsException'
            ]
        ];
    }
}
