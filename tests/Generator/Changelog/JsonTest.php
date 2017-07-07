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
            '_details' => [
                'release_date' => '2017-05-27',
                'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
            ],
            'added' => [
                'resources' => [
                    [
                        [
                            'The <span class="mill-changelog_method" data-mill-method="GET">GET</span> on <span ' .
                                'class="mill-changelog_uri" data-mill-uri="/movie/{id}">/movie/{id}</span> can now ' .
                                'throw the following errors:',
                            [
                                '<span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">404 Not ' .
                                    'Found</span> with a <span class="mill-changelog_representation" ' .
                                    'data-mill-representation="Error">Error</span> representation: For no reason.',
                                '<span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">404 Not ' .
                                    'Found</span> with a <span class="mill-changelog_representation" ' .
                                    'data-mill-representation="Error">Error</span> representation: For some other ' .
                                    'reason.'
                            ]
                        ]
                    ],
                    [
                        [
                            'The <span class="mill-changelog_method" data-mill-method="GET">GET</span> on <span ' .
                                'class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span> can ' .
                                'now throw the following errors:',
                            [
                                '<span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">404 Not ' .
                                    'Found</span> with a <span class="mill-changelog_representation" ' .
                                    'data-mill-representation="Error">Error</span> representation: For no reason.',
                                '<span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">404 Not ' .
                                    'Found</span> with a <span class="mill-changelog_representation" ' .
                                    'data-mill-representation="Error">Error</span> representation: For some other ' .
                                    'reason.'
                            ]
                        ],
                        '<span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span> on ' .
                            '<span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span> now ' .
                            'returns a <span class="mill-changelog_http_code" data-mill-http-code="404 Not Found">' .
                            '404 Not Found</span> with a <span class="mill-changelog_representation" ' .
                            'data-mill-representation="Error">Error</span> representation: If the trailer URL could ' .
                            'not be validated.'
                    ],
                    '<span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span> on <span ' .
                        'class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span> now returns a ' .
                        '<span class="mill-changelog_http_code" data-mill-http-code="202 Accepted">202 ' .
                        'Accepted</span> with a <span class="mill-changelog_representation" ' .
                        'data-mill-representation="Movie">Movie</span> representation.',
                    '<span class="mill-changelog_method" data-mill-method="POST">POST</span> on <span ' .
                        'class="mill-changelog_uri" data-mill-uri="/movies">/movies</span> now returns a <span ' .
                        'class="mill-changelog_http_code" data-mill-http-code="201 Created">201 Created</span>.'
                ]
            ],
            'removed' => [
                'representations' => [
                    //'external_urls.tickets has been removed from the Movie representation.'
                    '<span class="mill-changelog_field" data-mill-field="external_urls.tickets">external_urls.tickets' .
                        '</span> has been removed from the <span class="mill-changelog_representation" ' .
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
                    '<span class="mill-changelog_method" data-mill-method="GET">GET</span> on <span ' .
                        'class="mill-changelog_uri" data-mill-uri="/movie/{id}">/movie/{id}</span> now returns a ' .
                        '<span class="mill-changelog_content_type" ' .
                        'data-mill-content-type="application/mill.example.movie">application/mill.example.movie' .
                        '</span> Content-Type header.',
                    [
                        [
                            '<span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span> now ' .
                                'returns a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header on the following HTTP ' .
                                'methods:',
                            [
                                '<span class="mill-changelog_method" data-mill-method="GET">GET</span>',
                                '<span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span>'
                            ]
                        ]
                    ],
                    [
                        [
                            '<span class="mill-changelog_uri" data-mill-uri="/movies">/movies</span> now returns a ' .
                                '<span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.movie">' .
                                'application/mill.example.movie</span> Content-Type header on the following HTTP ' .
                                'methods:',
                            [
                                '<span class="mill-changelog_method" data-mill-method="GET">GET</span>',
                                '<span class="mill-changelog_method" data-mill-method="POST">POST</span>'
                            ]
                        ]
                    ],
                    [
                        [
                            '<span class="mill-changelog_uri" data-mill-uri="/theaters/{id}">/theaters/{id}</span> ' .
                                'now returns a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header on the following HTTP ' .
                                'methods:',
                            [
                                '<span class="mill-changelog_method" data-mill-method="GET">GET</span>',
                                '<span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span>'
                            ]
                        ]
                    ],
                    [
                        [
                            '<span class="mill-changelog_uri" data-mill-uri="/theaters">/theaters</span> now ' .
                                'returns a <span class="mill-changelog_content_type" ' .
                                'data-mill-content-type="application/mill.example.theater">' .
                                'application/mill.example.theater</span> Content-Type header on the following HTTP ' .
                                'methods:',
                            [
                                '<span class="mill-changelog_method" data-mill-method="GET">GET</span>',
                                '<span class="mill-changelog_method" data-mill-method="POST">POST</span>'
                            ]
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
                    'A <span class="mill-changelog_parameter" data-mill-parameter="imdb">imdb</span> request ' .
                        'parameter was added to <span class="mill-changelog_method" data-mill-method="PATCH">PATCH' .
                        '</span> on <span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span>.'
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
                        [
                            'The following fields have been added to the <span class="mill-changelog_representation" ' .
                                'data-mill-representation="Movie">Movie</span> representation:',
                            [
                                '<span class="mill-changelog_field" data-mill-field="external_urls">external_urls' .
                                    '</span>',
                                '<span class="mill-changelog_field" data-mill-field="external_urls.imdb">' .
                                    'external_urls.imdb</span>',
                                '<span class="mill-changelog_field" data-mill-field="external_urls.tickets">' .
                                    'external_urls.tickets</span>',
                                '<span class="mill-changelog_field" data-mill-field="external_urls.trailer">' .
                                    'external_urls.trailer</span>'
                            ]
                        ]
                    ]
                ],
                'resources' => [
                    [
                        [
                            '<span class="mill-changelog_uri" data-mill-uri="/movies/{id}">/movies/{id}</span> has ' .
                                'been added with support for the following HTTP methods:',
                            [
                                '<span class="mill-changelog_method" data-mill-method="PATCH">PATCH</span>',
                                '<span class="mill-changelog_method" data-mill-method="DELETE">DELETE</span>'
                            ]
                        ]
                    ],
                    [
                        'A <span class="mill-changelog_parameter" data-mill-parameter="<span ' .
                            'class="mill-changelog_parameter" data-mill-parameter="page">page</span>"><span ' .
                            'class="mill-changelog_parameter" data-mill-parameter="page">page</span></span> request ' .
                            'parameter was added to <span class="mill-changelog_method" data-mill-method="GET">GET' .
                            '</span> on <span class="mill-changelog_uri" data-mill-uri="/movies">/movies</span>.',
                        [
                            'The following parameters have been added to <span class="mill-changelog_method" ' .
                                'data-mill-method="POST">POST</span> on <span class="mill-changelog_uri" ' .
                                'data-mill-uri="/movies">/movies</span>:',
                            [
                                '<span class="mill-changelog_parameter" data-mill-parameter="imdb">imdb</span>',
                                '<span class="mill-changelog_parameter" data-mill-parameter="trailer">trailer</span>'
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
                                        'The <span class="mill-changelog_method" data-mill-method="POST">POST' .
                                            '</span> on <span class="mill-changelog_uri" data-mill-uri="/movies">' .
                                            '/movies</span> will now return the following responses:',
                                        [
                                            '<span class="mill-changelog_http_code" ' .
                                                'data-mill-http-code="201 Created">201 Created</span>',
                                            '<span class="mill-changelog_http_code" ' .
                                                'data-mill-http-code="200 OK">200 OK</span> with a <span ' .
                                                'class="mill-changelog_representation" ' .
                                                'data-mill-representation="Movie">Movie</span> representation'
                                        ]
                                    ],
                                    '<span class="mill-changelog_method" data-mill-method="GET">GET</span> on <span ' .
                                        'class="mill-changelog_uri" data-mill-uri="/movies">/movies</span> now ' .
                                        'returns a <span class="mill-changelog_http_code" ' .
                                        'data-mill-http-code="200 OK">200 OK</span> with a <span ' .
                                        'class="mill-changelog_representation" ' .
                                        'data-mill-representation="Movie">Movie</span> representation.'
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
                                        'The <span class="mill-changelog_method" data-mill-method="POST">POST</span> ' .
                                            'on <span class="mill-changelog_uri" data-mill-uri="/movies">/movies' .
                                            '</span> no longer returns the following responses:',
                                        [
                                            '<span class="mill-changelog_http_code" ' .
                                                'data-mill-http-code="201 Created">201 Created</span>',
                                            '<span class="mill-changelog_http_code" data-mill-http-code="200 OK">' .
                                                '200 OK</span> with a <span class="mill-changelog_representation" ' .
                                                'data-mill-representation="Movie">Movie</span> representation'
                                        ]
                                    ],
                                    '<span class="mill-changelog_method" data-mill-method="GET">GET</span> on <span ' .
                                        'class="mill-changelog_uri" data-mill-uri="/movies">/movies</span> no longer ' .
                                        'returns a <span class="mill-changelog_http_code" ' .
                                        'data-mill-http-code="200 OK">200 OK</span> with a <span ' .
                                        'class="mill-changelog_representation" data-mill-representation="Movie">' .
                                        'Movie</span> representation.'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
