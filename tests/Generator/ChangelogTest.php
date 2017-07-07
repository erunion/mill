<?php
namespace Mill\Tests\Generator;

use Mill\Generator\Changelog;
use Mill\Tests\TestCase;

class ChangelogTest extends TestCase
{
    /**
     * @dataProvider providerTestGeneration
     * @param boolean $private_objects
     * @param array $capabilities
     * @param array $expected
     * @return void
     */
    public function testGeneration($private_objects, $capabilities, $expected)
    {
        $generator = new Changelog($this->getConfig());
        $generator->setLoadPrivateDocs($private_objects);
        $generator->setLoadCapabilityDocs($capabilities);
        $changelog = $generator->generate();

        $this->assertSame(array_keys($expected), array_keys($changelog));

        foreach ($expected as $version => $expected_changes) {
            $this->assertSame(
                $expected_changes,
                $changelog[$version],
                'Change for v' . $version . ' don\'t match up.'
            );
        }
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

    /**
     * @return array
     */
    public function providerTestGeneration()
    {
        // Save us the effort of copy and pasting the same base actions over and over.
        $actions = [
            '/movies' => [
                '1.1.3' => [
                    'added' => [
                        Changelog::CHANGE_ACTION_RETURN => [
                            [
                                'method' => 'POST',
                                'uri' => '/movies',
                                'http_code' => '201 Created',
                                'representation' => false
                            ]
                        ]
                    ]
                ],
                '1.1.2' => [
                    'changed' => [
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
                    ]
                ],
                '1.1' => [
                    'added' => [
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
            '/movies/{id}' => [
                '1.1.3' => [
                    'added' => [
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
                ],
                '1.1.2' => [
                    'changed' => [
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
                    ]
                ],
                '1.1.1' => [
                    'added' => [
                        Changelog::CHANGE_ACTION_PARAM => [
                            [
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'parameter' => 'imdb',
                                'description' => 'IMDB URL'
                            ]
                        ]
                    ]
                ],
                '1.1' => [
                    'added' => [
                        Changelog::CHANGE_ACTION => [
                            [
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}'
                            ]
                        ]
                    ]
                ]
            ],
            '/movie/{id}' => [
                '1.1.3' => [
                    'added' => [
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
                    ]
                ],
                '1.1.2' => [
                    'changed' => [
                        Changelog::CHANGE_CONTENT_TYPE => [
                            [
                                'method' => 'GET',
                                'uri' => '/movie/{id}',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ]
                    ]
                ]
            ],
            '/theaters' => [
                '1.1.2' => [
                    'changed' => [
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
            ],
            '/theaters/{id}' => [
                '1.1.2' => [
                    'changed' => [
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
                    ]
                ]
            ]
        ];

        $representations = [
            'Movie' => [
                '1.1.3' => [
                    'removed' => [
                        Changelog::CHANGE_REPRESENTATION_DATA => [
                            [
                                'field' => 'external_urls.tickets',
                                'representation' => 'Movie'
                            ]
                        ]
                    ]
                ],
                '1.1' => [
                    'added' => [
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
                ]
            ],
            'Theater' => [
                '1.1' => [
                    'removed' => [
                        Changelog::CHANGE_REPRESENTATION_DATA => [
                            [
                                'field' => 'website',
                                'representation' => 'Theater'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return [
            // Complete changelog. All documentation parsed.
            'complete-changelog' => [
                'private_objects' => true,
                'capabilities' => null,
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                '/movie/{id}' => $actions['/movie/{id}']['1.1.3']['added'],
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.3']['added'],
                                '/movies' => $actions['/movies']['1.1.3']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1.3']['removed']
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01'
                        ],
                        'changed' => [
                            'resources' => [
                                '/movie/{id}' => $actions['/movie/{id}']['1.1.2']['changed'],
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.2']['changed'],
                                '/movies' => $actions['/movies']['1.1.2']['changed'],
                                '/theaters/{id}' => $actions['/theaters/{id}']['1.1.2']['changed'],
                                '/theaters' => $actions['/theaters']['1.1.2']['changed']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.1']['added']
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01'
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1']['added']
                            ],
                            'resources' => [
                                '/movies/{id}' => call_user_func(function () use ($actions) {
                                    $action = $actions['/movies/{id}']['1.1']['added'];
                                    $action[Changelog::CHANGE_ACTION][] = [
                                        'method' => 'DELETE',
                                        'uri' => '/movies/{id}'
                                    ];

                                    return $action;
                                }),
                                '/movies' => $actions['/movies']['1.1']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => $representations['Theater']['1.1']['removed']
                            ]
                        ]
                    ]
                ]
            ],

            // Changelog with public-only parsed docs and all capabilities.
            'changelog-public-docs-with-all-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'BUY_TICKETS',
                    'DELETE_CONTENT',
                    'FEATURE_FLAG',
                    'MOVIE_RATINGS'
                ],
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.3']['added'],
                                '/movies' => $actions['/movies']['1.1.3']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1.3']['removed']
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01'
                        ],
                        'changed' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.2']['changed'],
                                '/movies' => $actions['/movies']['1.1.2']['changed'],
                                '/theaters/{id}' => $actions['/theaters/{id}']['1.1.2']['changed'],
                                '/theaters' => $actions['/theaters']['1.1.2']['changed']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.1']['added']
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01'
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1']['added']

                            ],
                            'resources' => [
                                '/movies/{id}' => call_user_func(function () use ($actions) {
                                    $action = $actions['/movies/{id}']['1.1']['added'];
                                    $action[Changelog::CHANGE_ACTION][] = [
                                        'method' => 'DELETE',
                                        'uri' => '/movies/{id}'
                                    ];

                                    return $action;
                                }),
                                '/movies' => $actions['/movies']['1.1']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => $representations['Theater']['1.1']['removed']
                            ]
                        ]
                    ]
                ]
            ],

            // Changelog with public-only parsed docs and unmatched capabilities
            'changelog-public-docs-with-unmatched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'BUY_TICKETS',
                    'FEATURE_FLAG'
                ],
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.3']['added'],
                                '/movies' => $actions['/movies']['1.1.3']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1.3']['removed']
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01'
                        ],
                        'changed' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.2']['changed'],
                                '/movies' => $actions['/movies']['1.1.2']['changed'],
                                '/theaters/{id}' => $actions['/theaters/{id}']['1.1.2']['changed'],
                                '/theaters' => $actions['/theaters']['1.1.2']['changed']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.1']['added']
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01'
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1']['added']
                            ],
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1']['added'],
                                '/movies' => $actions['/movies']['1.1']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => $representations['Theater']['1.1']['removed']
                            ]
                        ]
                    ]
                ],
            ],

            // Changelog with public-only parsed docs and matched capabilities
            'changelog-public-docs-with-matched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'DELETE_CONTENT'
                ],
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.3']['added'],
                                '/movies' => $actions['/movies']['1.1.3']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1.3']['removed']
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01'
                        ],
                        'changed' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.2']['changed'],
                                '/movies' => $actions['/movies']['1.1.2']['changed'],
                                '/theaters/{id}' => $actions['/theaters/{id}']['1.1.2']['changed'],
                                '/theaters' => $actions['/theaters']['1.1.2']['changed']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.1']['added']
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01'
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1']['added']
                            ],
                            'resources' => [
                                '/movies/{id}' => call_user_func(function () use ($actions) {
                                    $action = $actions['/movies/{id}']['1.1']['added'];
                                    $action[Changelog::CHANGE_ACTION][] = [
                                        'method' => 'DELETE',
                                        'uri' => '/movies/{id}'
                                    ];

                                    return $action;
                                }),
                                '/movies' => $actions['/movies']['1.1']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => $representations['Theater']['1.1']['removed']
                            ]
                        ]
                    ]
                ]
            ],

            // Changelog with public-only parsed docs
            'changelog-public-docs-with-unmatched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [],
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.3']['added'],
                                '/movies' => $actions['/movies']['1.1.3']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1.3']['removed']
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01',
                        ],
                        'changed' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.2']['changed'],
                                '/movies' => $actions['/movies']['1.1.2']['changed'],
                                '/theaters/{id}' => $actions['/theaters/{id}']['1.1.2']['changed'],
                                '/theaters' => $actions['/theaters']['1.1.2']['changed']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1.1']['added']
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01',
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => $representations['Movie']['1.1']['added']
                            ],
                            'resources' => [
                                '/movies/{id}' => $actions['/movies/{id}']['1.1']['added'],
                                '/movies' => $actions['/movies']['1.1']['added']
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => $representations['Theater']['1.1']['removed']
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }
}
