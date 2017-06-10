<?php
namespace Mill\Tests\Generator;

use Mill\Generator\Changelog;
use Mill\Tests\TestCase;

class ChangelogTest extends TestCase
{
    public function testGeneration()
    {
        $changelog = new Changelog($this->getConfig());
        $generated = $changelog->generate();

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
                    '/movie/{id}' => [
                        Changelog::CHANGE_ACTION_THROWS => [
                            [
                                'method' => 'GET',
                                'uri' => '/movie/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'For no reason.'
                            ],
                            [
                                'method' => 'GET',
                                'uri' => '/movie/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'For some other reason.'
                            ]
                        ]
                    ],
                    '/movies/{id}' => [
                        Changelog::CHANGE_ACTION_THROWS => [
                            [
                                'method' => 'GET',
                                'uri' => '/movies/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'For no reason.'
                            ],
                            [
                                'method' => 'GET',
                                'uri' => '/movies/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'For some other reason.'
                            ],
                            [
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'If the trailer URL could not be validated.'
                            ]
                        ],
                        Changelog::CHANGE_ACTION_RETURN => [
                            [
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'http_code' => '202 Accepted',
                                'representation' => 'Movie'
                            ]
                        ]
                    ],
                    '/movies' => [
                        Changelog::CHANGE_ACTION_RETURN => [
                            [
                                'method' => 'POST',
                                'uri' => '/movies',
                                'http_code' => '201 Created',
                                'representation' => false
                            ]
                        ]
                    ]
                ]
            ],
            'removed' => [
                'representations' => [
                    'Movie' => [
                        Changelog::CHANGE_REPRESENTATION_DATA => [
                            [
                                'field' => 'external_urls.tickets',
                                'representation' => 'Movie'
                            ]
                        ]
                    ]
                ]
            ]
        ], $generated['1.1.3']);

        // v1.1.2
        $this->assertSame([
            'changed' => [
                'resources' => [
                    '/movie/{id}' => [
                        Changelog::CHANGE_CONTENT_TYPE => [
                            [
                                'method' => 'GET',
                                'uri' => '/movie/{id}',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ]
                    ],
                    '/movies/{id}' => [
                        Changelog::CHANGE_CONTENT_TYPE => [
                            [
                                'method' => 'GET',
                                'uri' => '/movies/{id}',
                                'content_type' => 'application/mill.example.movie'
                            ],
                            [
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ]
                    ],
                    '/movies' => [
                        Changelog::CHANGE_CONTENT_TYPE => [
                            [
                                'method' => 'GET',
                                'uri' => '/movies',
                                'content_type' => 'application/mill.example.movie'
                            ],
                            [
                                'method' => 'POST',
                                'uri' => '/movies',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ]
                    ],
                    '/theaters/{id}' => [
                        Changelog::CHANGE_CONTENT_TYPE => [
                            [
                                'method' => 'GET',
                                'uri' => '/theaters/{id}',
                                'content_type' => 'application/mill.example.theater'
                            ],
                            [
                                'method' => 'PATCH',
                                'uri' => '/theaters/{id}',
                                'content_type' => 'application/mill.example.theater'
                            ]
                        ]
                    ],
                    '/theaters' => [
                        Changelog::CHANGE_CONTENT_TYPE => [
                            [
                                'method' => 'GET',
                                'uri' => '/theaters',
                                'content_type' => 'application/mill.example.theater'
                            ],
                            [
                                'method' => 'POST',
                                'uri' => '/theaters',
                                'content_type' => 'application/mill.example.theater'
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
                    '/movies/{id}' => [
                        Changelog::CHANGE_ACTION_PARAM => [
                            [
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'parameter' => 'imdb',
                                'description' => 'IMDB URL'
                            ]
                        ]
                    ]
                ]
            ]
        ], $generated['1.1.1']);

        // v1.1
        $this->assertSame([
            'added' => [
                'representations' => [
                    'Movie' => [
                        Changelog::CHANGE_REPRESENTATION_DATA => [
                            [
                                'field' => 'external_urls',
                                'representation' => 'Movie'
                            ],
                            [
                                'field' => 'external_urls.imdb',
                                'representation' => 'Movie'
                            ],
                            [
                                'field' => 'external_urls.tickets',
                                'representation' => 'Movie'
                            ],
                            [
                                'field' => 'external_urls.trailer',
                                'representation' => 'Movie'
                            ]
                        ]
                    ]
                ],
                'resources' => [
                    '/movies/{id}' => [
                        Changelog::CHANGE_ACTION => [
                            [
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}'
                            ],
                            [
                                'method' => 'DELETE',
                                'uri' => '/movies/{id}'
                            ]
                        ]
                    ],
                    '/movies' => [
                        Changelog::CHANGE_ACTION_PARAM => [
                            [
                                'method' => 'GET',
                                'uri' => '/movies',
                                'parameter' => 'page',
                                'description' => 'Page of results to pull.'
                            ],
                            [
                                'method' => 'POST',
                                'uri' => '/movies',
                                'parameter' => 'imdb',
                                'description' => 'IMDB URL'
                            ],
                            [
                                'method' => 'POST',
                                'uri' => '/movies',
                                'parameter' => 'trailer',
                                'description' => 'Trailer URL'
                            ]
                        ]
                    ]
                ]
            ]
        ], $generated['1.1']);
    }

    public function testJsonGeneration()
    {
        $changelog = new Changelog($this->getConfig());
        $generated = $changelog->generateJson();
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
            ]
        ], $generated['1.1']);
    }
}
