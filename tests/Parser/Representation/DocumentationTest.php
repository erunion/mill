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
        $parsed = (new Documentation($class, $method))->parse();
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

        (new Documentation($class, $method))->parse();
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
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'description' => [
                            'description' => 'Description',
                            'identifier' => 'description',
                            'nullable' => false,
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
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'object',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => '>=1.1'
                        ],
                        'external_urls.imdb' => [
                            'description' => 'IMDB URL',
                            'identifier' => 'external_urls.imdb',
                            'nullable' => false,
                            'sample_data' => false,
                            'scopes' => [],
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
                            'sample_data' => false,
                            'scopes' => [],
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
                            'sample_data' => false,
                            'scopes' => [],
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
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'array',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'id' => [
                            'description' => 'Unique ID',
                            'identifier' => 'id',
                            'nullable' => false,
                            'sample_data' => false,
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
                            'sample_data' => '0',
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
                            'sample_data' => false,
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
                            'nullable' => false,
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
                            'sample_data' => false,
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
                            'sample_data' => false,
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
                            'sample_data' => false,
                            'scopes' => [],
                            'subtype' => false,
                            'type' => 'array',
                            'values' => [],
                            'vendor_tags' => [],
                            'version' => false
                        ],
                        'theaters' => [
                            'description' => 'Theaters the movie is currently showing in',
                            'identifier' => 'theaters',
                            'nullable' => false,
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
                            'sample_data' => false,
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
                            '__FIELD_DATA__' => [
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
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'content_rating' => [
                            '__FIELD_DATA__' => [
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
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'description' => [
                            '__FIELD_DATA__' => [
                                'description' => 'Description',
                                'identifier' => 'description',
                                'nullable' => false,
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
                            '__FIELD_DATA__' => [
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
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'external_urls' => [
                            '__FIELD_DATA__' => [
                                'description' => 'External URLs',
                                'identifier' => 'external_urls',
                                'nullable' => false,
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'object',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => '>=1.1'
                            ],
                            'imdb' => [
                                '__FIELD_DATA__' => [
                                    'description' => 'IMDB URL',
                                    'identifier' => 'external_urls.imdb',
                                    'nullable' => false,
                                    'sample_data' => false,
                                    'scopes' => [],
                                    'subtype' => false,
                                    'type' => 'string',
                                    'values' => [],
                                    'vendor_tags' => [],
                                    'version' => '>=1.1'
                                ]
                            ],
                            'tickets' => [
                                '__FIELD_DATA__' => [
                                    'description' => 'Tickets URL',
                                    'identifier' => 'external_urls.tickets',
                                    'nullable' => false,
                                    'sample_data' => false,
                                    'scopes' => [],
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
                                '__FIELD_DATA__' => [
                                    'description' => 'Trailer URL',
                                    'identifier' => 'external_urls.trailer',
                                    'nullable' => false,
                                    'sample_data' => false,
                                    'scopes' => [],
                                    'subtype' => false,
                                    'type' => 'string',
                                    'values' => [],
                                    'vendor_tags' => [],
                                    'version' => '>=1.1'
                                ]
                            ]
                        ],
                        'genres' => [
                            '__FIELD_DATA__' => [
                                'description' => 'Genres',
                                'identifier' => 'genres',
                                'nullable' => false,
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'array',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'id' => [
                            '__FIELD_DATA__' => [
                                'description' => 'Unique ID',
                                'identifier' => 'id',
                                'nullable' => false,
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'number',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'kid_friendly' => [
                            '__FIELD_DATA__' => [
                                'description' => 'Kid friendly?',
                                'identifier' => 'kid_friendly',
                                'nullable' => false,
                                'sample_data' => '0',
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'boolean',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'name' => [
                            '__FIELD_DATA__' => [
                                'description' => 'Name',
                                'identifier' => 'name',
                                'nullable' => false,
                                'sample_data' => false,
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
                                '__FIELD_DATA__' => [
                                    'description' => 'URL to purchase the film.',
                                    'identifier' => 'purchase.url',
                                    'nullable' => false,
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
                            '__FIELD_DATA__' => [
                                'description' => 'Rotten Tomatoes score',
                                'identifier' => 'rotten_tomatoes_score',
                                'nullable' => false,
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'number',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'runtime' => [
                            '__FIELD_DATA__' => [
                                'description' => 'Runtime',
                                'identifier' => 'runtime',
                                'nullable' => false,
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'string',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'showtimes' => [
                            '__FIELD_DATA__' => [
                                'description' => 'Non-theater specific showtimes',
                                'identifier' => 'showtimes',
                                'nullable' => false,
                                'sample_data' => false,
                                'scopes' => [],
                                'subtype' => false,
                                'type' => 'array',
                                'values' => [],
                                'vendor_tags' => [],
                                'version' => false
                            ]
                        ],
                        'theaters' => [
                            '__FIELD_DATA__' => [
                                'description' => 'Theaters the movie is currently showing in',
                                'identifier' => 'theaters',
                                'nullable' => false,
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
                            '__FIELD_DATA__' => [
                                'description' => 'Movie URI',
                                'identifier' => 'uri',
                                'nullable' => false,
                                'sample_data' => false,
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
