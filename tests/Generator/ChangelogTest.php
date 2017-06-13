<?php
namespace Mill\Tests\Generator;

use Mill\Generator\Changelog;
use Mill\Tests\TestCase;

class ChangelogTest extends TestCase
{
    public function testGeneration()
    {
        $generator = new Changelog($this->getConfig());
        $changelog = $generator->generate();

        $this->assertSame([
            '1.1.3',
            '1.1.2',
            '1.1.1',
            '1.1'
        ], array_keys($changelog));

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
        ], $changelog['1.1.3']);

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
        ], $changelog['1.1.2']);

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
        ], $changelog['1.1.1']);

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
            ],
            'removed' => [
                'representations' => [
                    'Theater' => [
                        'representation_data' => [
                            [
                                'field' => 'website',
                                'representation' => 'Theater'
                            ]
                        ]
                    ]
                ]
            ]
        ], $changelog['1.1']);
    }

    public function testJsonGeneration()
    {
        $generator = new Changelog($this->getConfig());
        $changelog = $generator->generateJson();
        $changelog = json_decode($changelog, true);

        // We don't need to test the full functionality of the JSON extension to the Changelog generator, since that's
        // being done in `Generator\Changelog\JsonTest`, we just want to make sure that we at least have the expected
        // array keys.
        $this->assertSame([
            '1.1.3',
            '1.1.2',
            '1.1.1',
            '1.1'
        ], array_keys($changelog));
    }
}
