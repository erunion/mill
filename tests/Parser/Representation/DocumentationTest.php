<?php
namespace Mill\Tests\Parser\Representation;

use Mill\Parser\Representation\Documentation;
use Mill\Tests\TestCase;

class DocumentationTest extends TestCase
{
    /**
     * @dataProvider providerParseDocumentationReturnsRepresentation
     * @param string $class
     * @param string $method
     * @param array $expected
     * @return void
     */
    public function testParseDocumentationReturnsRepresentation($class, $method, array $expected)
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
     * @param string $class
     * @param string $method
     * @param string $exception
     * @return void
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
                            'version' => '>=1.1 <1.1.3'
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
                    ],
                    'content.exploded' => [
                        'cast' => [
                            '__FIELD_DATA__' => [
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
                            ]
                        ],
                        'content_rating' => [
                            '__FIELD_DATA__' => [
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
                            ]
                        ],
                        'description' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Description',
                                'identifier' => 'description',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'director' => [
                            '__FIELD_DATA__' => [
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
                            ]
                        ],
                        'external_urls' => [
                            '__FIELD_DATA__' => [
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
                            'imdb' => [
                                '__FIELD_DATA__' => [
                                    'capability' => false,
                                    'description' => 'IMDB URL',
                                    'identifier' => 'external_urls.imdb',
                                    'sample_data' => false,
                                    'scopes' => [],
                                    'subtype' => false,
                                    'type' => 'string',
                                    'values' => false,
                                    'version' => '>=1.1'
                                ]
                            ],
                            'tickets' => [
                                '__FIELD_DATA__' => [
                                    'capability' => 'BUY_TICKETS',
                                    'description' => 'Tickets URL',
                                    'identifier' => 'external_urls.tickets',
                                    'sample_data' => false,
                                    'scopes' => [],
                                    'subtype' => false,
                                    'type' => 'string',
                                    'values' => false,
                                    'version' => '>=1.1 <1.1.3'
                                ]
                            ],
                            'trailer' => [
                                '__FIELD_DATA__' => [
                                    'capability' => false,
                                    'description' => 'Trailer URL',
                                    'identifier' => 'external_urls.trailer',
                                    'sample_data' => false,
                                    'scopes' => [],
                                    'subtype' => false,
                                    'type' => 'string',
                                    'values' => false,
                                    'version' => '>=1.1'
                                ]
                            ]
                        ],
                        'genres' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Genres',
                                'identifier' => 'genres',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'array',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'id' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Unique ID',
                                'identifier' => 'id',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'number',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'kid_friendly' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Kid friendly?',
                                'identifier' => 'kid_friendly',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'boolean',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'name' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Name',
                                'identifier' => 'name',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'purchase' => [
                            'url' => [
                                '__FIELD_DATA__' => [
                                    'capability' => false,
                                    'description' => 'URL to purchase the film.',
                                    'identifier' => 'purchase.url',
                                    'sample_data' => false,
                                    'scopes' => [],
                                    'subtype' => false,
                                    'type' => 'string',
                                    'values' => false,
                                    'version' => false
                                ]
                            ]
                        ],
                        'rotten_tomatoes_score' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Rotten Tomatoes score',
                                'identifier' => 'rotten_tomatoes_score',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'number',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'runtime' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Runtime',
                                'identifier' => 'runtime',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'string',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'showtimes' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Non-theater specific showtimes',
                                'identifier' => 'showtimes',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'array',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'theaters' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'description' => 'Theaters the movie is currently showing in',
                                'identifier' => 'theaters',
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                                'type' => 'array',
                                'values' => false,
                                'version' => false
                            ]
                        ],
                        'uri' => [
                            '__FIELD_DATA__' => [
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
                'expected.exception' => 'Mill\Exceptions\Annotations\RequiredAnnotationException',
                'asserts' => [
                    'getAnnotation' => 'label'
                ]
            ],
            'multiple-label-annotations' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithMultipleLabelAnnotations',
                'method' => 'create',
                'expected.exception' => '\Mill\Exceptions\Annotations\MultipleAnnotationsException'
            ]
        ];
    }
}
