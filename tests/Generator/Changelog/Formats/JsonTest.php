<?php
namespace Mill\Tests\Generator\Changelog\Formats;

use Mill\Generator\Changelog;
use Mill\Generator\Changelog\Formats\Json;
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
            '_details' => [
                'release_date' => '2017-05-27',
                'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
            ],
            'added' => [
                'resources' => [
                    [
                        'The following <span class="mill-changelog_group" data-mill-group="Movies">Movies</span> ' .
                            'resources have added:',
                        [
                            [
                                '<span class="mill-changelog_uri" data-mill-uri="/movie/{id}">/movie/{id}</span> ' .
                                    'will now throw the following errors on <span class="mill-changelog_method" ' .
                                    'data-mill-method="GET">GET</span> requests:',
                                [
                                    '<span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-representation="Error">Error</span> representation: For no reason.',
                                    '<span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-representation="Error">Error</span> representation: For some ' .
                                        'other reason.'
                                ]
                            ],
                            [
                                '<span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span> ' .
                                    'will now throw the following errors on <span class="mill-changelog_method" ' .
                                    'data-mill-method="GET">GET</span> requests:',
                                [
                                    '<span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-representation="Error">Error</span> representation: For no reason.',
                                    '<span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">404 ' .
                                        'Not Found</span> with a <span class="mill-changelog_representation" ' .
                                        'data-mill-representation="Error">Error</span> representation: For some ' .
                                        'other reason.'
                                ]
                            ],
                            'On <span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span> requests ' .
                                'to <span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}' .
                                '</span>, a <span class="mill-changelog_http_code" ' .
                                'data-mill-http-code="404 Not Found">404 Not Found</span> with a <span ' .
                                'class="mill-changelog_representation" data-mill-representation="Error">Error</span> ' .
                                'representation will now be returned: If the trailer URL could not be validated.',
                            'On <span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span>, ' .
                                '<span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span> requests ' .
                                'now returns a <span class="mill-changelog_http_code" ' .
                                'data-mill-http-code="202 Accepted">202 Accepted</span> with a <span ' .
                                'class="mill-changelog_representation" data-mill-representation="Movie">Movie' .
                                '</span> representation.',
                            '<span class="mill-changelog_method" data-mill-method="POST">POST</span> on <span ' .
                                'class="mill-changelog_uri" data-mill-uri="/movies">/movies</span> now returns a ' .
                                '<span class="mill-changelog_http_code" data-mill-http-code="201 Created">201 ' .
                                'Created</span>.'
                        ]
                    ]
                ]
            ],
            'removed' => [
                'representations' => [
                    '<span class="mill-changelog_field" data-mill-field="external_urls.tickets">' .
                        'external_urls.tickets</span> has been removed from the <span ' .
                        'class="mill-changelog_representation" data-mill-representation="Movie">Movie</span> ' .
                        'representation.'
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
                        'The following <span class="mill-changelog_group" data-mill-group="Movies">Movies</span> ' .
                            'resources have changed:',
                        [
                            'On <span class="mill-changelog_uri" data-mill-uri="/movie/{id}">/movie/{id}</span>, ' .
                                '<span class="mill-changelog_method" data-mill-method="GET">GET</span> requests will ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span>, ' .
                                '<span class="mill-changelog_method" data-mill-method="GET">GET</span> requests will ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span>, ' .
                                '<span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span> requests ' .
                                'will return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-uri="/movies">/movies</span>, <span ' .
                                'class="mill-changelog_method" data-mill-method="GET">GET</span> requests will ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-uri="/movies">/movies</span>, <span ' .
                                'class="mill-changelog_method" data-mill-method="POST">POST</span> requests will ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header.'
                        ]
                    ],
                    [
                        'The following <span class="mill-changelog_group" data-mill-group="Theaters">Theaters' .
                            '</span> resources have changed:',
                        [
                            'On <span class="mill-changelog_uri" data-mill-uri="/theaters/{id}">/theaters/{id}' .
                                '</span>, <span class="mill-changelog_method" data-mill-method="GET">GET</span> ' .
                                'requests will return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-uri="/theaters/{id}">/theaters/{id}' .
                                '</span>, <span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span> ' .
                                'requests will return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-uri="/theaters">/theaters</span>, <span ' .
                                'class="mill-changelog_method" data-mill-method="GET">GET</span> requests will ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.',
                            'On <span class="mill-changelog_uri" data-mill-uri="/theaters">/theaters</span>, <span ' .
                                'class="mill-changelog_method" data-mill-method="POST">POST</span> requests will ' .
                                'return a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header.'
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
                        'The following <span class="mill-changelog_group" data-mill-group="Movies">Movies</span> ' .
                            'resources have added:',
                        [
                            'A <span class="mill-changelog_parameter" data-mill-parameter="imdb">imdb</span> request ' .
                                'parameter was added to <span class="mill-changelog_method" ' .
                                'data-mill-method="PATCH">PATCH</span> on <span class="mill-changelog_uri" ' .
                                'data-mill-uri="/movies/{id}">/movies/{id}</span>.'
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
                        'The <span class="mill-changelog_representation" data-mill-representation="Movie">Movie' .
                            '</span> has added the following fields:',
                        [
                            '<span class="mill-changelog_field" data-mill-field="external_urls">external_urls</span>',
                            '<span class="mill-changelog_field" data-mill-field="external_urls.imdb">' .
                                'external_urls.imdb</span>',
                            '<span class="mill-changelog_field" data-mill-field="external_urls.tickets">' .
                                'external_urls.tickets</span>',
                            '<span class="mill-changelog_field" data-mill-field="external_urls.trailer">' .
                                'external_urls.trailer</span>'
                        ]
                    ]
                ],
                'resources' => [
                    [
                        'The following <span class="mill-changelog_group" data-mill-group="Movies">Movies</span> ' .
                            'resources have added:',
                        [
                            [
                                '<span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span> ' .
                                    'has been added with support for the following HTTP methods:',
                                [
                                    '<span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span>',
                                    '<span class="mill-changelog_method" data-mill-method="DELETE">DELETE</span>'
                                ]
                            ],
                            'A <span class="mill-changelog_parameter" data-mill-parameter="page">page</span> request ' .
                                'parameter was added to <span class="mill-changelog_method" ' .
                                'data-mill-method="GET">GET</span> on <span class="mill-changelog_uri" ' .
                                'data-mill-uri="/movies">/movies</span>.',
                            [
                                'The following parameters have been added to <span class="mill-changelog_method" ' .
                                    'data-mill-method="POST">POST</span> on <span class="mill-changelog_uri" ' .
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
                    '<span class="mill-changelog_field" data-mill-field="website">website</span> has been removed ' .
                        'from the <span class="mill-changelog_representation" data-mill-representation="Theater">' .
                        'Theater</span> representation.'
                ]
            ]
        ], $generated['1.1'], '1.1 changelog does not match');
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
                                'Movies' => [
                                    '/movies' => [
                                        Changelog::CHANGE_ACTION_RETURN => [
                                            'hashed-set.1' => [
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
                                                ]
                                            ],
                                            'hashed-set.2' => [
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
                        ]
                    ]
                ],
                'expected' => [
                    '1.1.3' => [
                        'added' => [
                            'resources' => [
                                [
                                    'The following <span class="mill-changelog_group" data-mill-group="Movies">' .
                                        'Movies</span> resources have added:',
                                    [
                                        [
                                            'The <span class="mill-changelog_method" data-mill-method="POST">POST' .
                                                '</span> on <span class="mill-changelog_uri" ' .
                                                'data-mill-uri="/movies">/movies</span> will now return the ' .
                                                'following responses:',
                                            [
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-http-code="201 Created">201 Created</span>',
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-http-code="200 OK">200 OK</span> with a <span ' .
                                                    'class="mill-changelog_representation" ' .
                                                    'data-mill-representation="Movie">Movie</span> representation'
                                            ]
                                        ],
                                        'On <span class="mill-changelog_uri" data-mill-uri="/movies">/movies</span>, ' .
                                            '<span class="mill-changelog_method" data-mill-method="GET">GET</span> ' .
                                            'requests now returns a <span class="mill-changelog_http_code" ' .
                                            'data-mill-http-code="200 OK">200 OK</span> with a <span ' .
                                            'class="mill-changelog_representation" data-mill-representation="Movie">' .
                                            'Movie</span> representation.'
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
                                        Changelog::CHANGE_ACTION_RETURN => [
                                            'hashed-set.1' => [
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
                                                ]
                                            ],
                                            'hashed-set.2' => [
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
                        ]
                    ]
                ],
                'expected' => [
                    '1.1.3' => [
                        'removed' => [
                            'resources' => [
                                [
                                    'The following <span class="mill-changelog_group" data-mill-group="Movies">' .
                                        'Movies</span> resources have removed:',
                                    [
                                        [
                                            'The <span class="mill-changelog_method" data-mill-method="POST">POST' .
                                                '</span> on <span class="mill-changelog_uri" ' .
                                                'data-mill-uri="/movies">/movies</span> no longer returns the ' .
                                                'following responses:',
                                            [
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-http-code="201 Created">201 Created</span>',
                                                '<span class="mill-changelog_http_code" ' .
                                                    'data-mill-http-code="200 OK">200 OK</span> with a <span ' .
                                                    'class="mill-changelog_representation" ' .
                                                    'data-mill-representation="Movie">Movie</span> representation'
                                            ]
                                        ],
                                        'On <span class="mill-changelog_uri" data-mill-uri="/movies">/movies</span>, ' .
                                            '<span class="mill-changelog_method" data-mill-method="GET">GET</span> ' .
                                            'requests no longer returns a <span class="mill-changelog_http_code" ' .
                                            'data-mill-http-code="200 OK">200 OK</span> with a <span ' .
                                            'class="mill-changelog_representation" data-mill-representation="Movie">' .
                                            'Movie</span> representation.'
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
