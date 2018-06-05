<?php
namespace Mill\Tests\Compiler\Changelog\Formats;

use Mill\Compiler\Changelog;
use Mill\Compiler\Changelog\Formats\Json;
use Mill\Tests\TestCase;

class JsonTest extends TestCase
{
    public function testCompiler(): void
    {
        $compiler = new Json($this->getConfig());
        $compiler->setChangelog((new Changelog($this->getConfig()))->compile());
        $compiled = $compiler->compile();
        $compiled = array_shift($compiled);
        $compiled = json_decode($compiled, true);

        $this->assertSame([
            '1.1.3',
            '1.1.2',
            '1.1.1',
            '1.1'
        ], array_keys($compiled));

        // v1.1.3
        $this->assertSame([
            '_details' => [
                'release_date' => '2017-05-27',
                'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
            ],
            'added' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_group" ' .
                            'data-mill-resource-group="Movies">Movies</span> resources have added:',
                        [
                            [
                                '<span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                    'data-mill-method="GET" data-mill-path="/movie/{id}">/movie/{id}</span> now ' .
                                    'returns the following errors on <span class="mill-changelog_method" ' .
                                    'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                    'data-mill-path="/movie/{id}">GET</span> requests:',
                                [
                                    '<span class="mill-changelog_http_code" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="GET" data-mill-path="/movie/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                        'data-mill-path="/movie/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: For no reason.',
                                    '<span class="mill-changelog_http_code" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="GET" data-mill-path="/movie/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                        'data-mill-path="/movie/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: For some ' .
                                        'other reason.'
                                ]
                            ],
                            [
                                '<span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                    'data-mill-method="GET" data-mill-path="/movies/{id}">/movies/{id}</span> now ' .
                                    'returns the following errors on <span class="mill-changelog_method" ' .
                                    'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                    'data-mill-path="/movies/{id}">GET</span> requests:',
                                [
                                    '<span class="mill-changelog_http_code" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="GET" data-mill-path="/movies/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                        'data-mill-path="/movies/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: For no reason.',
                                    '<span class="mill-changelog_http_code" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="GET" data-mill-path="/movies/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                        'data-mill-path="/movies/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: For some ' .
                                        'other reason.'
                                ]
                            ],
                            [
                                '<span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                    'data-mill-method="PATCH" data-mill-path="/movies/{id}">/movies/{id}</span> now ' .
                                    'returns the following errors on <span class="mill-changelog_method" ' .
                                    'data-mill-resource-group="Movies" data-mill-method="PATCH" ' .
                                    'data-mill-path="/movies/{id}">PATCH</span> requests:',
                                [
                                    '<span class="mill-changelog_http_code" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="PATCH" data-mill-path="/movies/{id}" ' .
                                        'data-mill-http-code="404 Not Found" data-mill-representation="Error">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="PATCH" ' .
                                        'data-mill-path="/movies/{id}" data-mill-http-code="404 Not Found" ' .
                                        'data-mill-representation="Error">Error</span> representation: If the ' .
                                        'trailer URL could not be validated.',
                                    '<span class="mill-changelog_http_code" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="PATCH" data-mill-path="/movies/{id}" ' .
                                        'data-mill-http-code="403 Forbidden" data-mill-representation="Coded error">' .
                                        '403 Forbidden</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="PATCH" ' .
                                        'data-mill-path="/movies/{id}" data-mill-http-code="403 Forbidden" ' .
                                        'data-mill-representation="Coded error">Coded error</span> representation: ' .
                                        'If something cool happened.',
                                    '<span class="mill-changelog_http_code" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="PATCH" data-mill-path="/movies/{id}" ' .
                                        'data-mill-http-code="403 Forbidden" data-mill-representation="Coded error">' .
                                        '403 Forbidden</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="PATCH" ' .
                                        'data-mill-path="/movies/{id}" data-mill-http-code="403 Forbidden" ' .
                                        'data-mill-representation="Coded error">Coded error</span> representation: ' .
                                        'If the user is not allowed to edit that movie.'
                                ]
                            ],
                            'On <span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                'data-mill-method="PATCH" data-mill-path="/movies/{id}" ' .
                                'data-mill-http-code="202 Accepted" data-mill-representation="Movie">/movies/{id}' .
                                '</span>, <span class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                'data-mill-method="PATCH" data-mill-path="/movies/{id}" ' .
                                'data-mill-http-code="202 Accepted" data-mill-representation="Movie">PATCH</span> ' .
                                'requests now return a <span class="mill-changelog_http_code" ' .
                                'data-mill-resource-group="Movies" data-mill-method="PATCH" ' .
                                'data-mill-path="/movies/{id}" data-mill-http-code="202 Accepted" ' .
                                'data-mill-representation="Movie">202 Accepted</span> with a <span ' .
                                'class="mill-changelog_representation" data-mill-resource-group="Movies" ' .
                                'data-mill-method="PATCH" data-mill-path="/movies/{id}" ' .
                                'data-mill-http-code="202 Accepted" data-mill-representation="Movie">Movie</span> ' .
                                'representation.',
                            '<span class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                'data-mill-method="POST" data-mill-path="/movies" data-mill-http-code="201 Created" ' .
                                'data-mill-representation="">POST</span> on <span class="mill-changelog_path" ' .
                                'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                'data-mill-path="/movies" data-mill-http-code="201 Created" ' .
                                'data-mill-representation="">/movies</span> now returns a <span ' .
                                'class="mill-changelog_http_code" data-mill-resource-group="Movies" ' .
                                'data-mill-method="POST" data-mill-path="/movies" data-mill-http-code="201 Created" ' .
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
        ], $compiled['1.1.3'], '1.1.3 changelog does not match');

        // v1.1.2
        $this->assertSame([
            '_details' => [
                'release_date' => '2017-04-01'
            ],
            'changed' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_group" ' .
                            'data-mill-resource-group="Movies">Movies</span> resources have changed:',
                        [
                            'On <span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                'data-mill-method="GET" data-mill-path="/movie/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">/movie/{id}</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                'data-mill-method="GET" data-mill-path="/movie/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                'data-mill-path="/movie/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                'data-mill-method="GET" data-mill-path="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">/movies/{id}</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                'data-mill-method="GET" data-mill-path="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                'data-mill-path="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                'data-mill-method="PATCH" data-mill-path="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">/movies/{id}</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                'data-mill-method="PATCH" data-mill-path="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">PATCH</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Movies" data-mill-method="PATCH" ' .
                                'data-mill-path="/movies/{id}" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                'data-mill-method="GET" data-mill-path="/movies" ' .
                                'data-mill-content-type="application/mill.example.movie">/movies</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                'data-mill-method="GET" data-mill-path="/movies" ' .
                                'data-mill-content-type="application/mill.example.movie">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                'data-mill-path="/movies" data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                'data-mill-method="POST" data-mill-path="/movies" ' .
                                'data-mill-content-type="application/mill.example.movie">/movies</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                'data-mill-method="POST" data-mill-path="/movies" ' .
                                'data-mill-content-type="application/mill.example.movie">POST</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                'data-mill-path="/movies" data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.'
                        ]
                    ],
                    [
                        'The following <span class="mill-changelog_resource_group" ' .
                            'data-mill-resource-group="Theaters">Theaters</span> resources have changed:',
                        [
                            'On <span class="mill-changelog_path" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="GET" data-mill-path="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">/theaters/{id}</span>, ' .
                                '<span class="mill-changelog_method" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="GET" data-mill-path="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Theaters" data-mill-method="GET" ' .
                                'data-mill-path="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_path" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="PATCH" data-mill-path="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">/theaters/{id}</span>, ' .
                                '<span class="mill-changelog_method" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="PATCH" data-mill-path="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">PATCH</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Theaters" data-mill-method="PATCH" ' .
                                'data-mill-path="/theaters/{id}" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_path" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="GET" data-mill-path="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">/theaters</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="GET" data-mill-path="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">GET</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Theaters" data-mill-method="GET" ' .
                                'data-mill-path="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_path" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="POST" data-mill-path="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">/theaters</span>, <span ' .
                                'class="mill-changelog_method" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="POST" data-mill-path="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">POST</span> requests now ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-resource-group="Theaters" data-mill-method="POST" ' .
                                'data-mill-path="/theaters" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.'
                        ]
                    ]
                ]
            ],
            'removed' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_group" ' .
                            'data-mill-resource-group="Theaters">Theaters</span> resources have removed:',
                        [
                            '<span class="mill-changelog_method" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="PATCH" data-mill-path="/theaters/{id}" ' .
                                'data-mill-http-code="403 Forbidden" data-mill-representation="Coded error">PATCH' .
                                '</span> requests to <span class="mill-changelog_path" ' .
                                'data-mill-resource-group="Theaters" data-mill-method="PATCH" ' .
                                'data-mill-path="/theaters/{id}" data-mill-http-code="403 Forbidden" ' .
                                'data-mill-representation="Coded error">/theaters/{id}</span> no longer returns a ' .
                                '<span class="mill-changelog_http_code" data-mill-resource-group="Theaters" ' .
                                'data-mill-method="PATCH" data-mill-path="/theaters/{id}" ' .
                                'data-mill-http-code="403 Forbidden" data-mill-representation="Coded error">403 ' .
                                'Forbidden</span> with a <span class="mill-changelog_representation" ' .
                                'data-mill-resource-group="Theaters" data-mill-method="PATCH" ' .
                                'data-mill-path="/theaters/{id}" data-mill-http-code="403 Forbidden" ' .
                                'data-mill-representation="Coded error">Coded error</span> representation: If ' .
                                'something cool happened.'
                        ]
                    ]
                ]
            ]
        ], $compiled['1.1.2'], '1.1.2 changelog does not match');

        // v1.1.1
        $this->assertSame([
            '_details' => [
                'release_date' => '2017-03-01'
            ],
            'added' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_resource_group" ' .
                            'data-mill-resource-group="Movies">Movies</span> resources have added:',
                        [
                            'A <span class="mill-changelog_parameter" data-mill-resource-group="Movies" ' .
                                'data-mill-method="PATCH" data-mill-path="/movies/{id}" data-mill-parameter="imdb">' .
                                'imdb</span> request parameter was added to <span class="mill-changelog_method" ' .
                                'data-mill-resource-group="Movies" data-mill-method="PATCH" ' .
                                'data-mill-path="/movies/{id}" data-mill-parameter="imdb">PATCH</span> on <span ' .
                                'class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                'data-mill-method="PATCH" data-mill-path="/movies/{id}" data-mill-parameter="imdb">' .
                                '/movies/{id}</span>.'
                        ]
                    ]
                ]
            ]
        ], $compiled['1.1.1'], '1.1.1 changelog does not match');

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
                        'The following <span class="mill-changelog_resource_group" ' .
                            'data-mill-resource-group="Movies">Movies</span> resources have added:',
                        [
                            [
                                '<span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                    'data-mill-path="/movies/{id}">/movies/{id}</span> has been added with support ' .
                                    'for the following HTTP methods:',
                                [
                                    '<span class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="PATCH" data-mill-path="/movies/{id}">PATCH</span>',
                                    '<span class="mill-changelog_method" data-mill-resource-group="Movies" ' .
                                        'data-mill-method="DELETE" data-mill-path="/movies/{id}">DELETE</span>'
                                ]
                            ],
                            'A <span class="mill-changelog_parameter" data-mill-resource-group="Movies" ' .
                                'data-mill-method="GET" data-mill-path="/movies" data-mill-parameter="page">page' .
                                '</span> request parameter was added to <span class="mill-changelog_method" ' .
                                'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                'data-mill-path="/movies" data-mill-parameter="page">GET</span> on <span ' .
                                'class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                'data-mill-method="GET" data-mill-path="/movies" data-mill-parameter="page">' .
                                '/movies</span>.',
                            [
                                'The following parameters have been added to <span class="mill-changelog_method" ' .
                                    'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                    'data-mill-path="/movies">POST</span> on <span class="mill-changelog_path" ' .
                                    'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                    'data-mill-path="/movies">/movies</span>:',
                                [
                                    '<span class="mill-changelog_parameter" data-mill-parameter="imdb" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                        'data-mill-path="/movies">imdb</span>',
                                    '<span class="mill-changelog_parameter" data-mill-parameter="trailer" ' .
                                        'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                        'data-mill-path="/movies">trailer</span>'
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
        ], $compiled['1.1'], '1.1 changelog does not match');
    }

    /**
     * @dataProvider providerTestCompilerCases
     * @param array $changelog
     * @param array $expected
     */
    public function testCompilerCases(array $changelog, array $expected): void
    {
        $compiler = new Json($this->getConfig());
        $compiler->setChangelog($changelog);
        $compiled = $compiler->compile();
        $compiled = array_shift($compiled);
        $compiled = json_decode($compiled, true);

        $this->assertSame($expected, $compiled);
    }

    public function providerTestCompilerCases(): array
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
                                                    'resource_group' => 'Movies',
                                                    'method' => 'POST',
                                                    'path' => '/movies',
                                                    'http_code' => '201 Created',
                                                    'representation' => false
                                                ],
                                                [
                                                    'resource_group' => 'Movies',
                                                    'method' => 'POST',
                                                    'path' => '/movies',
                                                    'http_code' => '200 OK',
                                                    'representation' => 'Movie'
                                                ]
                                            ],
                                            'hashed-set.2' => [
                                                [
                                                    'resource_group' => 'Movies',
                                                    'method' => 'GET',
                                                    'path' => '/movies',
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
                                    'The following <span class="mill-changelog_resource_group" ' .
                                        'data-mill-resource-group="Movies">Movies</span> resources have added:',
                                    [
                                        [
                                            'The <span class="mill-changelog_method" ' .
                                                'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                                'data-mill-path="/movies">POST</span> on <span ' .
                                                'class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                                'data-mill-method="POST" data-mill-path="/movies">/movies</span> now ' .
                                                'returns the following responses:',
                                            [
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                                    'data-mill-path="/movies" data-mill-http-code="201 Created" ' .
                                                    'data-mill-representation="">201 Created</span>',
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                                    'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
                                                    'data-mill-representation="Movie">200 OK</span> with a <span ' .
                                                    'class="mill-changelog_representation" ' .
                                                    'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                                    'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
                                                    'data-mill-representation="Movie">Movie</span> representation'
                                            ]
                                        ],
                                        'On <span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                            'data-mill-method="GET" data-mill-path="/movies" ' .
                                            'data-mill-http-code="200 OK" data-mill-representation="Movie">/movies' .
                                            '</span>, <span class="mill-changelog_method" ' .
                                            'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                            'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">GET</span> requests now return a ' .
                                            '<span class="mill-changelog_http_code" ' .
                                            'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                            'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">200 OK</span> with a <span ' .
                                            'class="mill-changelog_representation" ' .
                                            'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                            'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
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
                                                    'resource_group' => 'Movies',
                                                    'method' => 'POST',
                                                    'path' => '/movies',
                                                    'http_code' => '201 Created',
                                                    'representation' => false
                                                ],
                                                [
                                                    'resource_group' => 'Movies',
                                                    'method' => 'POST',
                                                    'path' => '/movies',
                                                    'http_code' => '200 OK',
                                                    'representation' => 'Movie'
                                                ]
                                            ],
                                            'hashed-set.2' => [
                                                [
                                                    'resource_group' => 'Movies',
                                                    'method' => 'GET',
                                                    'path' => '/movies',
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
                                    'The following <span class="mill-changelog_resource_group" ' .
                                        'data-mill-resource-group="Movies">Movies</span> resources have removed:',
                                    [
                                        [
                                            'The <span class="mill-changelog_method" ' .
                                                'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                                'data-mill-path="/movies">POST</span> on <span ' .
                                                'class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                                'data-mill-method="POST" data-mill-path="/movies">/movies</span> no ' .
                                                'longer returns the following responses:',
                                            [
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                                    'data-mill-path="/movies" data-mill-http-code="201 Created" ' .
                                                    'data-mill-representation="">201 Created</span>',
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                                    'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
                                                    'data-mill-representation="Movie">200 OK</span> with a <span ' .
                                                    'class="mill-changelog_representation" ' .
                                                    'data-mill-resource-group="Movies" data-mill-method="POST" ' .
                                                    'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
                                                    'data-mill-representation="Movie">Movie</span> representation'
                                            ]
                                        ],
                                        'On <span class="mill-changelog_path" data-mill-resource-group="Movies" ' .
                                            'data-mill-method="GET" data-mill-path="/movies" ' .
                                            'data-mill-http-code="200 OK" data-mill-representation="Movie">/movies' .
                                            '</span>, <span class="mill-changelog_method" ' .
                                            'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                            'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">GET</span> requests no longer return ' .
                                            'a <span class="mill-changelog_http_code" ' .
                                            'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                            'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
                                            'data-mill-representation="Movie">200 OK</span> with a <span ' .
                                            'class="mill-changelog_representation" ' .
                                            'data-mill-resource-group="Movies" data-mill-method="GET" ' .
                                            'data-mill-path="/movies" data-mill-http-code="200 OK" ' .
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
