<?php
namespace Mill\Tests\Parser\Representation;

use Mill\Exceptions\Annotations\MultipleAnnotationsException;
use Mill\Exceptions\Annotations\RequiredAnnotationException;
use Mill\Exceptions\Resource\NoAnnotationsException;
use Mill\Parser\Representation\Documentation;
use Mill\Tests\TestCase;

class DocumentationTest extends TestCase
{
    /**
     * @dataProvider providerParseDocumentationReturnsRepresentation
     * @param string $class
     * @param string $method
     * @param array $expected
     */
    public function testParseDocumentationReturnsRepresentation(string $class, string $method, array $expected): void
    {
        $parsed = (new Documentation($class, $method, $this->getApplication()))->parse();
        $representation = $parsed->toArray();

        $this->assertSame($class, $parsed->getClass());
        $this->assertSame($method, $parsed->getMethod());

        $this->assertSame($expected['label'], $parsed->getLabel());
        $this->assertSame($expected['label'], $representation['label']);
        $this->assertSame($expected['description.length'], strlen($representation['description']));

        // Verify content dot notation.
        $this->assertSame(array_keys($expected['content']), array_keys($parsed->getRawContent()));
        $this->assertSame(array_keys($expected['content']), array_keys($parsed->getContent()));
        $this->assertSame(array_keys($expected['content']), array_keys($representation['content']));
        $this->assertCount(count($expected['content']), $representation['content']);
        foreach ($representation['content'] as $annotation => $data) {
            $this->assertSame($expected['content'][$annotation], $data, '`' . $annotation . '` mismatch');
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
     */
    public function testParseDocumentationFailsOnBadRepresentations(
        string $class,
        string $method,
        string $exception
    ): void {
        $this->expectException($exception);

        (new Documentation($class, $method, $this->getApplication()))->parse();
    }

    public function providerParseDocumentationReturnsRepresentation(): array
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
                    ],
                    'content.exploded' => [
                        'cast' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'content_rating' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'description' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'director' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'external_urls' => [
                            '__NESTED_DATA__' => [
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
                            'imdb' => [
                                '__NESTED_DATA__' => [
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
                                ]
                            ],
                            'tickets' => [
                                '__NESTED_DATA__' => [
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
                                ]
                            ],
                            'trailer' => [
                                '__NESTED_DATA__' => [
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
                                ]
                            ]
                        ],
                        'genres' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'id' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'kid_friendly' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'name' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'purchase' => [
                            'url' => [
                                '__NESTED_DATA__' => [
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
                                ]
                            ]
                        ],
                        'rotten_tomatoes_score' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'runtime' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'showtimes' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'theaters' => [
                            '__NESTED_DATA__' => [
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
                            ]
                        ],
                        'uri' => [
                            '__NESTED_DATA__' => [
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
            ]
        ];
    }

    public function providerParseDocumentationFailsOnBadRepresentations(): array
    {
        return [
            'no-annotations' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithNoAnnotations',
                'method' => 'create',
                'expected.exception' => NoAnnotationsException::class
            ],
            'no-annotations-on-the-class' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithNoClassAnnotations',
                'method' => 'create',
                'expected.exception' => NoAnnotationsException::class
            ],
            'missing-a-required-label-annotation' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithRequiredLabelAnnotationMissing',
                'method' => 'create',
                'expected.exception' => RequiredAnnotationException::class,
                'asserts' => [
                    'getAnnotation' => 'label'
                ]
            ],
            'multiple-label-annotations' => [
                'class' => '\Mill\Tests\Fixtures\Representations\RepresentationWithMultipleLabelAnnotations',
                'method' => 'create',
                'expected.exception' => MultipleAnnotationsException::class
            ]
        ];
    }
}
