<?php
namespace Mill\Tests\Generator\Changelog\Formats;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Formats\Json;
use Mill\Tests\TestCase;

class JsonTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new Json($this->getConfig());
        $generator->setChangelog((new Changelog($this->getConfig()))->generate());
        $generated = $generator->generate();
        $generated = array_shift($generated);
        $generated = json_decode($generated, true);

        $this->assertSame([
            '1.1.3',
            '1.1.2',
            '1.1.1',
            '1.1'
        ], array_keys($generated));

        // v1.1.3
        $this->assertSame([
            '_details' => [
                'release_date' => '2017-05-27',
                'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
            ],
            'added' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_namespace" ' .
                            'data-mill-resource-namespace="Movies">Movies</span> resources have added:',
                        [
                            [
                                '<span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                    'data-mill-method="GET" data-mill-uri="/movie/{id}">/movie/{id}</span> now ' .
                                    'throws the following errors on <span class="mill-changelog_method" ' .
                                    'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                    'data-mill-uri="/movie/{id}">GET</span> requests:',
                                [
                                    '<span class="mill-changelog_http_code" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="GET" data-mill-uri="/movie/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                        'data-mill-uri="/movie/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: For no reason.',
                                    '<span class="mill-changelog_http_code" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="GET" data-mill-uri="/movie/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                        'data-mill-uri="/movie/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: For some ' .
                                        'other reason.'
                                ]
                            ],
                            [
                                '<span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                    'data-mill-method="GET" data-mill-uri="/movies/{id}">/movies/{id}</span> now ' .
                                    'throws the following errors on <span class="mill-changelog_method" ' .
                                    'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                    'data-mill-uri="/movies/{id}">GET</span> requests:',
                                [
                                    '<span class="mill-changelog_http_code" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="GET" data-mill-uri="/movies/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                        'data-mill-uri="/movies/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: For no reason.',
                                    '<span class="mill-changelog_http_code" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="GET" data-mill-uri="/movies/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                        'data-mill-uri="/movies/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: For some ' .
                                        'other reason.'
                                ]
                            ],
                            [
                                '<span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                    'data-mill-method="PATCH" data-mill-uri="/movies/{id}">/movies/{id}</span> now ' .
                                    'throws the following errors on <span class="mill-changelog_method" ' .
                                    'data-mill-resource-namespace="Movies" data-mill-method="PATCH" ' .
                                    'data-mill-uri="/movies/{id}">PATCH</span> requests:',
                                [
                                    '<span class="mill-changelog_http_code" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="PATCH" data-mill-uri="/movies/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-namespace="Movies" data-mill-method="PATCH" ' .
                                        'data-mill-uri="/movies/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: If the ' .
                                        'trailer URL could not be validated.',
                                    '<span class="mill-changelog_http_code" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="PATCH" data-mill-uri="/movies/{id}" ' .
                                        'data-mill-http-code="403 Forbidden" data-mill-representation="Coded error">' .
                                        '403 Forbidden</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-namespace="Movies" data-mill-method="PATCH" ' .
                                        'data-mill-uri="/movies/{id}" data-mill-http-code="403 Forbidden" ' .
                                        'data-mill-representation="Coded error">Coded error</span> representation: ' .
                                        'If something cool happened.',
                                    '<span class="mill-changelog_http_code" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="PATCH" data-mill-uri="/movies/{id}" ' .
                                        'data-mill-http-code="403 Forbidden" data-mill-representation="Coded error">' .
                                        '403 Forbidden</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-namespace="Movies" data-mill-method="PATCH" ' .
                                        'data-mill-uri="/movies/{id}" data-mill-http-code="403 Forbidden" ' .
                                        'data-mill-representation="Coded error">Coded error</span> representation: ' .
                                        'If the user is not allowed to edit that movie.'
                                ]
                            ],
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="PATCH" data-mill-uri="/movies/{id}" ' .
                                'data-mill-http-code="202 Accepted" data-mill-representation="Movie">/movies/{id}' .
                                '</span>, <span class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="PATCH" data-mill-uri="/movies/{id}" ' .
                                'data-mill-http-code="202 Accepted" data-mill-representation="Movie">PATCH</span> ' .
                                'requests now return a <span class="mill-changelog_http_code" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="PATCH" ' .
                                'data-mill-uri="/movies/{id}" data-mill-http-code="202 Accepted" ' .
                                'data-mill-representation="Movie">202 Accepted</span> with a <span ' .
                                'class="mill-changelog_representation" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="PATCH" data-mill-uri="/movies/{id}" ' .
                                'data-mill-http-code="202 Accepted" data-mill-representation="Movie">Movie</span> ' .
                                'representation.',
                            '<span class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="POST" data-mill-uri="/movies" data-mill-http-code="201 Created" ' .
                                'data-mill-representation="">POST</span> on <span class="mill-changelog_uri" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                'data-mill-uri="/movies" data-mill-http-code="201 Created" ' .
                                'data-mill-representation="">/movies</span> now returns a <span ' .
                                'class="mill-changelog_http_code" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="POST" data-mill-uri="/movies" data-mill-http-code="201 Created" ' .
                                'data-mill-representation="">201 Created</span>.'
                        ]
                    ]
                ]
            ],
            'removed' => [
                'representations' => [
                    '<span class="mill-changelog_field" data-mill-field="external_urls.tickets" ' .
                        'data-mill-representation="Movie">external_urls.tickets</span> has been removed from the ' .
                        '<span class="mill-changelog_representation" data-mill-field="external_urls.tickets" ' .
                        'data-mill-representation="Movie">Movie</span> representation.'
                ]
            ]
        ], $generated['1.1.3'], '1.1.3 changelog does not match');

        // v1.1.2
        $this->assertSame([
            '_details' => [
                'release_date' => '2017-04-01'
            ],
            'changed' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_namespace" ' .
                            'data-mill-resource-namespace="Movies">Movies</span> resources have changed:',
                        [
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="GET" data-mill-uri="/movie/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">/movie/{id}</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="GET" data-mill-uri="/movie/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                'data-mill-uri="/movie/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="GET" data-mill-uri="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">/movies/{id}</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="GET" data-mill-uri="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                'data-mill-uri="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="PATCH" data-mill-uri="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">/movies/{id}</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="PATCH" data-mill-uri="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">PATCH</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="PATCH" ' .
                                'data-mill-uri="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="GET" data-mill-uri="/movies" ' .
                                'data-mill-content-type="application/mill.example.movie">/movies</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="GET" data-mill-uri="/movies" ' .
                                'data-mill-content-type="application/mill.example.movie">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                'data-mill-uri="/movies" data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="POST" data-mill-uri="/movies" ' .
                                'data-mill-content-type="application/mill.example.movie">/movies</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="POST" data-mill-uri="/movies" ' .
                                'data-mill-content-type="application/mill.example.movie">POST</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                'data-mill-uri="/movies" data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.'
                        ]
                    ],
                    [
                        'The following <span class="mill-changelog_resource_namespace" ' .
                            'data-mill-resource-namespace="Theaters">Theaters</span> resources have changed:',
                        [
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="GET" data-mill-uri="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">/theaters/{id}</span>, ' .
                                '<span class="mill-changelog_method" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="GET" data-mill-uri="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Theaters" data-mill-method="GET" ' .
                                'data-mill-uri="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="PATCH" data-mill-uri="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">/theaters/{id}</span>, ' .
                                '<span class="mill-changelog_method" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="PATCH" data-mill-uri="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">PATCH</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Theaters" data-mill-method="PATCH" ' .
                                'data-mill-uri="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="GET" data-mill-uri="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">/theaters</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="GET" data-mill-uri="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Theaters" data-mill-method="GET" ' .
                                'data-mill-uri="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="POST" data-mill-uri="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">/theaters</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="POST" data-mill-uri="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">POST</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-namespace="Theaters" data-mill-method="POST" ' .
                                'data-mill-uri="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.'
                        ]
                    ]
                ]
            ],
            'removed' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_namespace" ' .
                            'data-mill-resource-namespace="Theaters">Theaters</span> resources have removed:',
                        [
                            '<span class="mill-changelog_method" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="PATCH" data-mill-uri="/theaters/{id}" ' .
                                'data-mill-http-code="403 Forbidden" data-mill-representation="Coded error">PATCH' .
                                '</span> requests to <span class="mill-changelog_uri" ' .
                                'data-mill-resource-namespace="Theaters" data-mill-method="PATCH" ' .
                                'data-mill-uri="/theaters/{id}" data-mill-http-code="403 Forbidden" ' .
                                'data-mill-representation="Coded error">/theaters/{id}</span> no longer returns a ' .
                                '<span class="mill-changelog_http_code" data-mill-resource-namespace="Theaters" ' .
                                'data-mill-method="PATCH" data-mill-uri="/theaters/{id}" ' .
                                'data-mill-http-code="403 Forbidden" data-mill-representation="Coded error">403 ' .
                                'Forbidden</span> with a <span class="mill-changelog_representation" ' .
                                'data-mill-resource-namespace="Theaters" data-mill-method="PATCH" ' .
                                'data-mill-uri="/theaters/{id}" data-mill-http-code="403 Forbidden" ' .
                                'data-mill-representation="Coded error">Coded error</span> representation: If ' .
                                'something cool happened.'
                        ]
                    ]
                ]
            ]
        ], $generated['1.1.2'], '1.1.2 changelog does not match');

        // v1.1.1
        $this->assertSame([
            '_details' => [
                'release_date' => '2017-03-01'
            ],
            'added' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_namespace" ' .
                            'data-mill-resource-namespace="Movies">Movies</span> resources have added:',
                        [
                            'A <span class="mill-changelog_parameter" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="PATCH" data-mill-uri="/movies/{id}" data-mill-parameter="imdb">' .
                                'imdb</span> request parameter was added to <span class="mill-changelog_method" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="PATCH" ' .
                                'data-mill-uri="/movies/{id}" data-mill-parameter="imdb">PATCH</span> on <span ' .
                                'class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="PATCH" data-mill-uri="/movies/{id}" data-mill-parameter="imdb">' .
                                '/movies/{id}</span>.'
                        ]
                    ]
                ]
            ]
        ], $generated['1.1.1'], '1.1.1 changelog does not match');

        // v1.1
        $this->assertSame([
            '_details' => [
                'release_date' => '2017-02-01'
            ],
            'added' => [
                'representations' => [
                    [
                        'The <span class="mill-changelog_representation" data-mill-field="external_urls" ' .
                            'data-mill-representation="Movie">Movie</span> representation has added the following ' .
                            'fields:',
                        [
                            '<span class="mill-changelog_field" data-mill-field="external_urls" ' .
                                'data-mill-representation="Movie">external_urls</span>',
                            '<span class="mill-changelog_field" data-mill-field="external_urls.imdb" ' .
                                'data-mill-representation="Movie">external_urls.imdb</span>',
                            '<span class="mill-changelog_field" data-mill-field="external_urls.tickets" ' .
                                'data-mill-representation="Movie">external_urls.tickets</span>',
                            '<span class="mill-changelog_field" data-mill-field="external_urls.trailer" ' .
                                'data-mill-representation="Movie">external_urls.trailer</span>'
                        ]
                    ]
                ],
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_namespace" ' .
                            'data-mill-resource-namespace="Movies">Movies</span> resources have added:',
                        [
                            [
                                '<span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                    'data-mill-uri="/movies/{id}">/movies/{id}</span> has been added with support ' .
                                    'for the following HTTP methods:',
                                [
                                    '<span class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="PATCH" data-mill-uri="/movies/{id}">PATCH</span>',
                                    '<span class="mill-changelog_method" data-mill-resource-namespace="Movies" ' .
                                        'data-mill-method="DELETE" data-mill-uri="/movies/{id}">DELETE</span>'
                                ]
                            ],
                            'A <span class="mill-changelog_parameter" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="GET" data-mill-uri="/movies" data-mill-parameter="page">page' .
                                '</span> request parameter was added to <span class="mill-changelog_method" ' .
                                'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                'data-mill-uri="/movies" data-mill-parameter="page">GET</span> on <span ' .
                                'class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                'data-mill-method="GET" data-mill-uri="/movies" data-mill-parameter="page">' .
                                '/movies</span>.',
                            [
                                'The following parameters have been added to <span class="mill-changelog_method" ' .
                                    'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                    'data-mill-uri="/movies">POST</span> on <span class="mill-changelog_uri" ' .
                                    'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                    'data-mill-uri="/movies">/movies</span>:',
                                [
                                    '<span class="mill-changelog_parameter" data-mill-parameter="imdb">imdb</span>',
                                    '<span class="mill-changelog_parameter" data-mill-parameter="trailer">trailer' .
                                        '</span>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'removed' => [
                'representations' => [
                    '<span class="mill-changelog_field" data-mill-field="website" ' .
                        'data-mill-representation="Theater">website</span> has been removed from the <span ' .
                        'class="mill-changelog_representation" data-mill-field="website" ' .
                        'data-mill-representation="Theater">Theater</span> representation.'
                ]
            ]
        ], $generated['1.1'], '1.1 changelog does not match');
    }

    /**
     * @dataProvider providerTestGenerateCases
     * @param array $changelog
     * @param array $expected
     */
    public function testGenerateCases(array $changelog, array $expected): void
    {
        $generator = new Json($this->getConfig());
        $generator->setChangelog($changelog);
        $generated = $generator->generate();
        $generated = array_shift($generated);
        $generated = json_decode($generated, true);

        $this->assertSame($expected, $generated);
    }

    public function providerTestGenerateCases(): array
    {
        return [
            'added-multiple-action-returns' => [
                'changelog' => [
                    '1.1.3' => [
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            'hashed-set.1' => [
                                                [
                                                    'resource_namespace' => 'Movies',
                                                    'method' => 'POST',
                                                    'uri' => '/movies',
                                                    'http_code' => '201 Created',
                                                    'representation' => false
                                                ],
                                                [
                                                    'resource_namespace' => 'Movies',
                                                    'method' => 'POST',
                                                    'uri' => '/movies',
                                                    'http_code' => '200 OK',
                                                    'representation' => 'Movie'
                                                ]
                                            ],
                                            'hashed-set.2' => [
                                                [
                                                    'resource_namespace' => 'Movies',
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
                        ]
                    ]
                ],
                'expected' => [
                    '1.1.3' => [
                        'added' => [
                            'resources' => [
                                [
                                    'The following <span class="mill-changelog_resource_namespace" ' .
                                        'data-mill-resource-namespace="Movies">Movies</span> resources have added:',
                                    [
                                        [
                                            'The <span class="mill-changelog_method" ' .
                                                'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                                'data-mill-uri="/movies">POST</span> on <span ' .
                                                'class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                                'data-mill-method="POST" data-mill-uri="/movies">/movies</span> now ' .
                                                'returns the following responses:',
                                            [
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                                    'data-mill-uri="/movies" data-mill-http-code="201 Created" ' .
                                                    'data-mill-representation="">201 Created</span>',
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                                    'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                                    'data-mill-representation="Movie">200 OK</span> with a <span ' .
                                                    'class="mill-changelog_representation" ' .
                                                    'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                                    'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                                    'data-mill-representation="Movie">Movie</span> representation'
                                            ]
                                        ],
                                        'On <span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                            'data-mill-method="GET" data-mill-uri="/movies" ' .
                                            'data-mill-http-code="200 OK" data-mill-representation="Movie">/movies' .
                                            '</span>, <span class="mill-changelog_method" ' .
                                            'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                            'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">GET</span> requests now return a ' .
                                            '<span class="mill-changelog_http_code" ' .
                                            'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                            'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">200 OK</span> with a <span ' .
                                            'class="mill-changelog_representation" ' .
                                            'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                            'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">Movie</span> representation.'
                                    ]
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
                                'Movies' => [
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            'hashed-set.1' => [
                                                [
                                                    'resource_namespace' => 'Movies',
                                                    'method' => 'POST',
                                                    'uri' => '/movies',
                                                    'http_code' => '201 Created',
                                                    'representation' => false
                                                ],
                                                [
                                                    'resource_namespace' => 'Movies',
                                                    'method' => 'POST',
                                                    'uri' => '/movies',
                                                    'http_code' => '200 OK',
                                                    'representation' => 'Movie'
                                                ]
                                            ],
                                            'hashed-set.2' => [
                                                [
                                                    'resource_namespace' => 'Movies',
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
                        ]
                    ]
                ],
                'expected' => [
                    '1.1.3' => [
                        'removed' => [
                            'resources' => [
                                [
                                    'The following <span class="mill-changelog_resource_namespace" ' .
                                        'data-mill-resource-namespace="Movies">Movies</span> resources have removed:',
                                    [
                                        [
                                            'The <span class="mill-changelog_method" ' .
                                                'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                                'data-mill-uri="/movies">POST</span> on <span ' .
                                                'class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                                'data-mill-method="POST" data-mill-uri="/movies">/movies</span> no ' .
                                                'longer returns the following responses:',
                                            [
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                                    'data-mill-uri="/movies" data-mill-http-code="201 Created" ' .
                                                    'data-mill-representation="">201 Created</span>',
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                                    'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                                    'data-mill-representation="Movie">200 OK</span> with a <span ' .
                                                    'class="mill-changelog_representation" ' .
                                                    'data-mill-resource-namespace="Movies" data-mill-method="POST" ' .
                                                    'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                                    'data-mill-representation="Movie">Movie</span> representation'
                                            ]
                                        ],
                                        'On <span class="mill-changelog_uri" data-mill-resource-namespace="Movies" ' .
                                            'data-mill-method="GET" data-mill-uri="/movies" ' .
                                            'data-mill-http-code="200 OK" data-mill-representation="Movie">/movies' .
                                            '</span>, <span class="mill-changelog_method" ' .
                                            'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                            'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">GET</span> requests no longer return ' .
                                            'a <span class="mill-changelog_http_code" ' .
                                            'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                            'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">200 OK</span> with a <span ' .
                                            'class="mill-changelog_representation" ' .
                                            'data-mill-resource-namespace="Movies" data-mill-method="GET" ' .
                                            'data-mill-uri="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">Movie</span> representation.'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
