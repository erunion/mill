<?php
namespace Mill\Tests\Generator\Changelog;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Json;
use Mill\Tests\TestCase;

class JsonTest extends TestCase
{
    public function testGenerate()
    {
        $generator = new Json($this->getConfig());
        $generator->setChangelog((new Changelog($this->getConfig()))->generate());
        $generated = $generator->generate();
        $generated = json_decode($generated, true);

        $this->assertSame([
            '1.1.3',
            '1.1.2',
            '1.1.1',
            '1.1'
        ], array_keys($generated));

        // v1.1.3
        $this->assertSame([
            'added' => [
                'resources' => [
                    [
                        [
                            'The GET on `/movie/{id}` can now throw the following errors:',
                            [
                                '`404 Not Found` with a `Error` representation: For no reason.',
                                '`404 Not Found` with a `Error` representation: For some other reason.'
                            ]
                        ]
                    ],
                    [
                        [
                            'The GET on `/movies/{id}` can now throw the following errors:',
                            [
                                '`404 Not Found` with a `Error` representation: For no reason.',
                                '`404 Not Found` with a `Error` representation: For some other reason.'
                            ]
                        ],
                        'PATCH on `/movies/{id}` now returns a `404 Not Found` with a `Error` representation: If ' .
                        'the trailer URL could not be validated.'
                    ],
                    'PATCH on `/movies/{id}` now returns a `202 Accepted` with a `Movie` representation.',
                    'POST on `/movies` now returns a `201 Created`.'
                ]
            ],
            'removed' => [
                'representations' => [
                    '`external_urls.tickets` has been removed from the `Movie` representation.'
                ]
            ]
        ], $generated['1.1.3']);

        // v1.1.2
        $this->assertSame([
            'changed' => [
                'resources' => [
                    'GET on `/movie/{id}` now returns a `application/mill.example.movie` Content-Type header.',
                    [
                        [
                            '`/movies/{id}` now returns a `application/mill.example.movie` Content-Type header on ' .
                            'the following HTTP methods:',
                            [
                                '`GET`',
                                '`PATCH`'
                            ]
                        ]
                    ],
                    [
                        [
                            '`/movies` now returns a `application/mill.example.movie` Content-Type header on the ' .
                            'following HTTP methods:',
                            [
                                '`GET`',
                                '`POST`'
                            ]
                        ]
                    ],
                    [
                        [
                            '`/theaters/{id}` now returns a `application/mill.example.theater` Content-Type header ' .
                            'on the following HTTP methods:',
                            [
                                '`GET`',
                                '`PATCH`'
                            ]
                        ]
                    ],
                    [
                        [
                            '`/theaters` now returns a `application/mill.example.theater` Content-Type header on the ' .
                            'following HTTP methods:',
                            [
                                '`GET`',
                                '`POST`'
                            ]
                        ]
                    ]
                ]
            ]
        ], $generated['1.1.2']);

        // v1.1.1
        $this->assertSame([
            'added' => [
                'resources' => [
                    'A `imdb` request parameter was added to PATCH on `/movies/{id}`.'
                ]
            ]
        ], $generated['1.1.1']);

        // v1.1
        $this->assertSame([
            'added' => [
                'representations' => [
                    [
                        [
                            'The following fields have been added to the `Movie` representation:',
                            [
                                '`external_urls`',
                                '`external_urls.imdb`',
                                '`external_urls.tickets`',
                                '`external_urls.trailer`'
                            ]
                        ]
                    ],
                ],
                'resources' => [
                    [
                        [
                            '`/movies/{id}` has been added with support for the following HTTP methods:',
                            [
                                '`PATCH`',
                                '`DELETE`'
                            ]
                        ]
                    ],
                    [
                        'A `page` request parameter was added to GET on `/movies`.',
                        [
                            'The following parameters have been added to POST on `/movies`:',
                            [
                                '`imdb`',
                                '`trailer`'
                            ]
                        ]
                    ]
                ]
            ],
            'removed' => [
                'representations' => [
                    '`website` has been removed from the `Theater` representation.'
                ]
            ]
        ], $generated['1.1']);
    }

    /**
     * @dataProvider providerTestGenerateCases
     * @param array $changelog
     * @param array $expected
     * @return void
     */
    public function testGenerateCases(array $changelog, array $expected)
    {
        $generator = new Json($this->getConfig());
        $generator->setChangelog($changelog);
        $generated = $generator->generate();
        $generated = json_decode($generated, true);

        $this->assertSame($expected, $generated);
    }

    /**
     * @return array
     */
    public function providerTestGenerateCases()
    {
        return [
            'added-multiple-action-returns' => [
                'changelog' => [
                    '1.1.3' => [
                        'added' => [
                            'resources' => [
                                '/movies' => [
                                    Changelog::CHANGE_ACTION_RETURN => [
                                        [
                                            'method' => 'POST',
                                            'uri' => '/movies',
                                            'http_code' => '201 Created',
                                            'representation' => false
                                        ],
                                        [
                                            'method' => 'POST',
                                            'uri' => '/movies',
                                            'http_code' => '200 OK',
                                            'representation' => 'Movie'
                                        ],
                                        [
                                            'method' => 'GET',
                                            'uri' => '/movies',
                                            'http_code' => '200 OK',
                                            'representation' => 'Movie'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    '1.1.3' => [
                        'added' => [
                            'resources' => [
                                [
                                    [
                                        'The POST on `/movies` will now return the following responses:',
                                        [
                                            '`201 Created`',
                                            '`200 OK` with a `Movie` representation'
                                        ]
                                    ],
                                    'GET on `/movies` now returns a `200 OK` with a `Movie` representation.'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'removed-multiple-action-returns' => [
                'changelog' => [
                    '1.1.3' => [
                        'removed' => [
                            'resources' => [
                                '/movies' => [
                                    Changelog::CHANGE_ACTION_RETURN => [
                                        [
                                            'method' => 'POST',
                                            'uri' => '/movies',
                                            'http_code' => '201 Created',
                                            'representation' => false
                                        ],
                                        [
                                            'method' => 'POST',
                                            'uri' => '/movies',
                                            'http_code' => '200 OK',
                                            'representation' => 'Movie'
                                        ],
                                        [
                                            'method' => 'GET',
                                            'uri' => '/movies',
                                            'http_code' => '200 OK',
                                            'representation' => 'Movie'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    '1.1.3' => [
                        'removed' => [
                            'resources' => [
                                [
                                    [
                                        'The POST on `/movies` no longer returns the following responses:',
                                        [
                                            '`201 Created`',
                                            '`200 OK` with a `Movie` representation'
                                        ]
                                    ],
                                    'GET on `/movies` no longer returns a `200 OK` with a `Movie` representation.'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
