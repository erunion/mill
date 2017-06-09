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
                [
                    'key' => Changelog::CHANGE_ACTION_THROWS,
                    'data' => [
                        'method' => 'PATCH',
                        'uri' => '/movies/{id}',
                        'http_code' => '404 Not Found',
                        'representation' => 'Error',
                        'description' => 'If the trailer URL could not be validated.'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_ACTION_RETURN,
                    'data' => [
                        'method' => 'POST',
                        'uri' => '/movies',
                        'http_code' => '201 Created',
                        'representation' => false
                    ]
                ]
            ],
            'removed' => [
                [
                    'key' => Changelog::CHANGE_REPRESENTATION_DATA,
                    'data' => [
                        'identifier' => 'external_urls.tickets',
                        'representation' => 'Movie'
                    ]
                ]
            ]
        ], $generated['1.1.3']);

        // v1.1.2
        $this->assertSame([
            'changed' => [
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'GET',
                        'uri' => '/movie/{id}',
                        'content_type' => 'application/mill.example.movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'GET',
                        'uri' => '/movies/{id}',
                        'content_type' => 'application/mill.example.movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'PATCH',
                        'uri' => '/movies/{id}',
                        'content_type' => 'application/mill.example.movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'GET',
                        'uri' => '/movies',
                        'content_type' => 'application/mill.example.movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'POST',
                        'uri' => '/movies',
                        'content_type' => 'application/mill.example.movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'GET',
                        'uri' => '/theaters/{id}',
                        'content_type' => 'application/mill.example.theater'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'PATCH',
                        'uri' => '/theaters/{id}',
                        'content_type' => 'application/mill.example.theater'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'GET',
                        'uri' => '/theaters',
                        'content_type' => 'application/mill.example.theater'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_CONTENT_TYPE,
                    'data' => [
                        'method' => 'POST',
                        'uri' => '/theaters',
                        'content_type' => 'application/mill.example.theater'
                    ]
                ]
            ]
        ], $generated['1.1.2']);

        // v1.1.1
        $this->assertSame([
            'added' => [
                [
                    'key' => Changelog::CHANGE_ACTION_PARAM,
                    'data' => [
                        'method' => 'PATCH',
                        'uri' => '/movies/{id}',
                        'parameter' => 'imdb',
                        'description' => 'IMDB URL'
                    ]
                ]
            ]
        ], $generated['1.1.1']);

        // v1.1
        $this->assertSame([
            'added' => [
                [
                    'key' => Changelog::CHANGE_REPRESENTATION_DATA,
                    'data' => [
                        'identifier' => 'external_urls',
                        'representation' => 'Movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_REPRESENTATION_DATA,
                    'data' => [
                        'identifier' => 'external_urls.imdb',
                        'representation' => 'Movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_REPRESENTATION_DATA,
                    'data' => [
                        'identifier' => 'external_urls.tickets',
                        'representation' => 'Movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_REPRESENTATION_DATA,
                    'data' => [
                        'identifier' => 'external_urls.trailer',
                        'representation' => 'Movie'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_ACTION,
                    'data' => [
                        'method' => 'PATCH',
                        'uri' => '/movies/{id}'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_ACTION_PARAM,
                    'data' => [
                        'method' => 'POST',
                        'uri' => '/movies',
                        'parameter' => 'imdb',
                        'description' => 'IMDB URL'
                    ]
                ],
                [
                    'key' => Changelog::CHANGE_ACTION_PARAM,
                    'data' => [
                        'method' => 'POST',
                        'uri' => '/movies',
                        'parameter' => 'trailer',
                        'description' => 'Trailer URL'
                    ]
                ]
            ]
        ], $generated['1.1']);
    }

    public function testJsonGeneration()
    {
        $changelog = new Changelog($this->getConfig());
        $generated = $changelog->generateJson();

        $this->assertSame([
            '1.1.3' => [
                'added' => [
                    'PATCH on `/movies/{id}` will now return a `404 Not Found` with a `Error` representation: If ' .
                    'the trailer URL could not be validated.',
                    'POST on `/movies` now returns a `201 Created`.'
                ],
                'removed' => [
                    '`external_urls.tickets` has been removed from the `Movie` representation.'
                ]
            ],
            '1.1.2' => [
                'changed' => [
                    'GET on `/movie/{id}` will now return a `application/mill.example.movie` content type.',
                    'GET on `/movies/{id}` will now return a `application/mill.example.movie` content type.',
                    'PATCH on `/movies/{id}` will now return a `application/mill.example.movie` content type.',
                    'GET on `/movies` will now return a `application/mill.example.movie` content type.',
                    'POST on `/movies` will now return a `application/mill.example.movie` content type.',
                    'GET on `/theaters/{id}` will now return a `application/mill.example.theater` content type.',
                    'PATCH on `/theaters/{id}` will now return a `application/mill.example.theater` content type.',
                    'GET on `/theaters` will now return a `application/mill.example.theater` content type.',
                    'POST on `/theaters` will now return a `application/mill.example.theater` content type.'
                ]
            ],
            '1.1.1' => [
                'added' => [
                    'A `imdb` request parameter was added to PATCH on `/movies/{id}`.'
                ]
            ],
            '1.1' => [
                'added' => [
                    '`external_urls` has been added to the `Movie` representation.',
                    '`external_urls.imdb` has been added to the `Movie` representation.',
                    '`external_urls.tickets` has been added to the `Movie` representation.',
                    '`external_urls.trailer` has been added to the `Movie` representation.',
                    'PATCH on `/movies/{id}` was added.',
                    'A `imdb` request parameter was added to POST on `/movies`.',
                    'A `trailer` request parameter was added to POST on `/movies`.'
                ]
            ]
        ], json_decode($generated, true));
    }
}
