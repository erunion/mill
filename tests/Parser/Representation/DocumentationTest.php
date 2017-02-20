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
                        'uri' => [
                            'capability' => false,
                            'field' => 'uri',
                            'label' => 'Movie URI',
                            'options' => false,
                            'type' => 'uri',
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
                    ],
                    'content.exploded' => [
                        'cast' => [
                            '__FIELD_DATA__' => [
                                'capability' => false,
                                'field' => 'cast',
                                'label' => 'Cast',
                                'options' => false,
                                'subtype' => false,
                                'type' => 'array',
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
                                'type' => 'string',
                                'version' => false
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
                        ],
                        'urls' => [
                            '__FIELD_DATA__' => [
                                'capability' => 'NONE',
                                'field' => 'urls',
                                'label' => 'External URLs',
                                'options' => false,
                                'type' => 'object',
                                'version' => false
                            ],
                            'imdb' => [
                                '__FIELD_DATA__' => [
                                    'capability' => false,
                                    'field' => 'urls.imdb',
                                    'label' => 'IMDB URL',
                                    'options' => false,
                                    'type' => 'string',
                                    'version' => false
                                ]
                            ],
                            'tickets' => [
                                '__FIELD_DATA__' => [
                                    'capability' => false,
                                    'field' => 'urls.tickets',
                                    'label' => 'Tickets URL',
                                    'options' => false,
                                    'type' => 'string',
                                    'version' => false
                                ]
                            ],
                            'trailer' => [
                                '__FIELD_DATA__' => [
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
