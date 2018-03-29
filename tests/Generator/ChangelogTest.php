<?php
namespace Mill\Tests\Generator;

use Mill\Generator\Changelog;
use Mill\Tests\TestCase;

class ChangelogTest extends TestCase
{
    /**
     * @dataProvider providerTestGeneration
     * @param bool $private_objects
     * @param array|null $vendor_tags
     * @param array $expected
     */
    public function testGeneration(bool $private_objects, ?array $vendor_tags, array $expected): void
    {
        $generator = new Changelog($this->getConfig());
        $generator->setLoadPrivateDocs($private_objects);
        $generator->setLoadVendorTagDocs($vendor_tags);
        $changelog = $generator->generate();

        $this->assertSame(array_keys($expected), array_keys($changelog));

        foreach ($expected as $version => $expected_changes) {
            $this->assertSame(
                array_keys($expected_changes),
                array_keys($changelog[$version]),
                'Change for v' . $version . ' does not have the same array keys.'
            );

            foreach ($expected_changes as $section => $changes) {
                $this->assertSame(
                    $changes,
                    $changelog[$version][$section],
                    'The `' . $section . '` changes for v' . $version . ' don\'t match up.'
                );
            }
        }
    }

    public function testJsonGeneration(): void
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

    public function providerTestGeneration(): array
    {
        // Save us the effort of copy and pasting the same base actions over and over.
        $actions = [
            '1.1.3' => [
                '/movie/{id}' => [
                    'error' => [
                        '2e302f7f79' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'GET',
                                'uri' => '/movie/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'For no reason.'
                            ],
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'GET',
                                'uri' => '/movie/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'For some other reason.'
                            ]
                        ]
                    ]
                ],
                '/movies' => [
                    'return' => [
                        '3781891d58' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'POST',
                                'uri' => '/movies',
                                'http_code' => '201 Created',
                                'representation' => false
                            ]
                        ]
                    ]
                ],
                '/movies/{id}' => [
                    'error' => [
                        'e7dc298139' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'GET',
                                'uri' => '/movies/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'For no reason.'
                            ],
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'GET',
                                'uri' => '/movies/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'For some other reason.'
                            ]
                        ],
                        '162944fa14' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'http_code' => '404 Not Found',
                                'representation' => 'Error',
                                'description' => 'If the trailer URL could not be validated.'
                            ],
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'http_code' => '403 Forbidden',
                                'representation' => 'Coded error',
                                'description' => 'If the user is not allowed to edit that movie.'
                            ]
                        ]
                    ],
                    'return' => [
                        '162944fa14' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'http_code' => '202 Accepted',
                                'representation' => 'Movie'
                            ]
                        ]
                    ]
                ]
            ],
            '1.1.2' => [
                '/movie/{id}' => [
                    'content_type' => [
                        '979fc6e97f' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'GET',
                                'uri' => '/movie/{id}',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ]
                    ]
                ],
                '/movies' => [
                    'content_type' => [
                        '979fc6e97f' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'GET',
                                'uri' => '/movies',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ],
                        '066564ef49' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'POST',
                                'uri' => '/movies',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ]
                    ]
                ],
                '/movies/{id}' => [
                    'content_type' => [
                        '979fc6e97f' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'GET',
                                'uri' => '/movies/{id}',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ],
                        'f4628f751a' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'content_type' => 'application/mill.example.movie'
                            ]
                        ]
                    ]
                ],
                '/theaters' => [
                    'content_type' => [
                        '979fc6e97f' => [
                            [
                                'resource_namespace' => 'Theaters',
                                'method' => 'GET',
                                'uri' => '/theaters',
                                'content_type' => 'application/mill.example.theater'
                            ]
                        ],
                        '066564ef49' => [
                            [
                                'resource_namespace' => 'Theaters',
                                'method' => 'POST',
                                'uri' => '/theaters',
                                'content_type' => 'application/mill.example.theater'
                            ]
                        ]
                    ]
                ],
                '/theaters/{id}' => [
                    'action_error' => [
                        'b3a16c4d74' => [
                            [
                                'resource_namespace' => 'Theaters',
                                'method' => 'PATCH',
                                'uri' => '/theaters/{id}',
                                'http_code' => '403 Forbidden',
                                'representation' => 'Coded error',
                                'description' => 'If something cool happened.'
                            ]
                        ]
                    ],
                    'content_type' => [
                        '979fc6e97f' => [
                            [
                                'resource_namespace' => 'Theaters',
                                'method' => 'GET',
                                'uri' => '/theaters/{id}',
                                'content_type' => 'application/mill.example.theater'
                            ]
                        ],
                        'f4628f751a' => [
                            [
                                'resource_namespace' => 'Theaters',
                                'method' => 'PATCH',
                                'uri' => '/theaters/{id}',
                                'content_type' => 'application/mill.example.theater'
                            ]
                        ]
                    ]
                ]
            ],
            '1.1.1' => [
                '/movies/{id}' => [
                    'param' => [
                        '162944fa14' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}',
                                'parameter' => 'imdb',
                                'description' => 'IMDB URL'
                            ]
                        ]
                    ]
                ]
            ],
            '1.1' => [
                '/movies' => [
                    'param' => [
                        '776d02bb83' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'GET',
                                'uri' => '/movies',
                                'parameter' => 'page',
                                'description' => 'Page of results to pull.'
                            ]
                        ],
                        '3781891d58' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'POST',
                                'uri' => '/movies',
                                'parameter' => 'imdb',
                                'description' => 'IMDB URL'
                            ],
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'POST',
                                'uri' => '/movies',
                                'parameter' => 'trailer',
                                'description' => 'Trailer URL'
                            ]
                        ]
                    ]
                ],
                '/movies/{id}' => [
                    'action' => [
                        'd81e7058dd' => [
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'PATCH',
                                'uri' => '/movies/{id}'
                            ],
                            [
                                'resource_namespace' => 'Movies',
                                'method' => 'DELETE',
                                'uri' => '/movies/{id}'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $representations = [
            '1.1.3' => [
                'Movie' => [
                    'ba8ac44626' => [
                        [
                            'field' => 'external_urls.tickets',
                            'representation' => 'Movie'
                        ]
                    ]
                ]
            ],
            '1.1' => [
                'Movie' => [
                    'ba8ac44626' => [
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
                ],
                'Theater' => [
                    '4034255a2c' => [
                        [
                            'field' => 'website',
                            'representation' => 'Theater'
                        ]
                    ]
                ]
            ]
        ];

        return [
            // Complete changelog. All documentation parsed.
            'complete-changelog' => [
                'private_objects' => true,
                'vendor_tags' => null,
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movie/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            '2e302f7f79' => $actions['1.1.3']['/movie/{id}']['error']['2e302f7f79']
                                        ]
                                    ],
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            'e7dc298139' => $actions['1.1.3']['/movies/{id}']['error']['e7dc298139'],
                                            '162944fa14' => call_user_func(
                                                function () use ($actions): array {
                                                    $actions = $actions['1.1.3']['/movies/{id}']['error']['162944fa14'];

                                                    // Add in the "If something cool happened." exception. It's the
                                                    // only private/vendor tag-free exception we're testing, and it
                                                    // should be available on the complete changelog.
                                                    $actions[2] = $actions[1];
                                                    $actions[1] = [
                                                        'resource_namespace' => 'Movies',
                                                        'method' => 'PATCH',
                                                        'uri' => '/movies/{id}',
                                                        'http_code' => '403 Forbidden',
                                                        'representation' => 'Coded error',
                                                        'description' => 'If something cool happened.'
                                                    ];

                                                    return $actions;
                                                }
                                            )
                                        ],
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            '162944fa14' => $actions['1.1.3']['/movies/{id}']['return']['162944fa14']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            '3781891d58' => $actions['1.1.3']['/movies']['return']['3781891d58']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Movie' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        'ba8ac44626' => $representations['1.1.3']['Movie']['ba8ac44626']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01'
                        ],
                        'changed' => [
                            'resources' => [
                                'Movies' => [
                                    '/movie/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/movie/{id}']['content_type']['979fc6e97f']
                                        ]
                                    ],
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/movies/{id}']['content_type']['979fc6e97f'],
                                            'f4628f751a' =>
                                                $actions['1.1.2']['/movies/{id}']['content_type']['f4628f751a']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' => $actions['1.1.2']['/movies']['content_type']['979fc6e97f'],
                                            '066564ef49' => $actions['1.1.2']['/movies']['content_type']['066564ef49']
                                        ]
                                    ]
                                ],
                                'Theaters' => [
                                    '/theaters/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/theaters/{id}']['content_type']['979fc6e97f'],
                                            'f4628f751a' =>
                                                $actions['1.1.2']['/theaters/{id}']['content_type']['f4628f751a']
                                        ]
                                    ],
                                    '/theaters' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/theaters']['content_type']['979fc6e97f'],
                                            '066564ef49' => $actions['1.1.2']['/theaters']['content_type']['066564ef49']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'resources' => [
                                'Theaters' => [
                                    '/theaters/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            'b3a16c4d74' =>
                                                $actions['1.1.2']['/theaters/{id}']['action_error']['b3a16c4d74']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_PARAM => [
                                            '162944fa14' => $actions['1.1.1']['/movies/{id}']['param']['162944fa14']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01'
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        'ba8ac44626' => $representations['1.1']['Movie']['ba8ac44626']
                                    ]
                                ]
                            ],
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION => [
                                            'd81e7058dd' => $actions['1.1']['/movies/{id}']['action']['d81e7058dd']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_PARAM => [
                                            '776d02bb83' => $actions['1.1']['/movies']['param']['776d02bb83'],
                                            '3781891d58' => $actions['1.1']['/movies']['param']['3781891d58']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        '4034255a2c' => $representations['1.1']['Theater']['4034255a2c']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // Changelog with public-only parsed docs and all vendor tags.
            'changelog-public-docs-with-all-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:BUY_TICKETS',
                    'tag:DELETE_CONTENT',
                    'tag:FEATURE_FLAG',
                    'tag:MOVIE_RATINGS'
                ],
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            'e7dc298139' => $actions['1.1.3']['/movies/{id}']['error']['e7dc298139'],
                                            '162944fa14' => $actions['1.1.3']['/movies/{id}']['error']['162944fa14']
                                        ],
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            '162944fa14' => $actions['1.1.3']['/movies/{id}']['return']['162944fa14']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            '3781891d58' => $actions['1.1.3']['/movies']['return']['3781891d58']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Movie' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        'ba8ac44626' => $representations['1.1.3']['Movie']['ba8ac44626']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01'
                        ],
                        'changed' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/movies/{id}']['content_type']['979fc6e97f'],
                                            'f4628f751a' =>
                                                $actions['1.1.2']['/movies/{id}']['content_type']['f4628f751a']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' => $actions['1.1.2']['/movies']['content_type']['979fc6e97f'],
                                            '066564ef49' => $actions['1.1.2']['/movies']['content_type']['066564ef49']
                                        ]
                                    ]
                                ],
                                'Theaters' => [
                                    '/theaters/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/theaters/{id}']['content_type']['979fc6e97f'],
                                            'f4628f751a' =>
                                                $actions['1.1.2']['/theaters/{id}']['content_type']['f4628f751a']
                                        ]
                                    ],
                                    '/theaters' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/theaters']['content_type']['979fc6e97f'],
                                            '066564ef49' =>
                                                $actions['1.1.2']['/theaters']['content_type']['066564ef49']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'resources' => [
                                'Theaters' => [
                                    '/theaters/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            'b3a16c4d74' =>
                                                $actions['1.1.2']['/theaters/{id}']['action_error']['b3a16c4d74']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_PARAM => [
                                            '162944fa14' => $actions['1.1.1']['/movies/{id}']['param']['162944fa14']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01'
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        'ba8ac44626' => $representations['1.1']['Movie']['ba8ac44626']
                                    ]
                                ]
                            ],
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION => [
                                            'd81e7058dd' => $actions['1.1']['/movies/{id}']['action']['d81e7058dd']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_PARAM => [
                                            '776d02bb83' => $actions['1.1']['/movies']['param']['776d02bb83'],
                                            '3781891d58' => $actions['1.1']['/movies']['param']['3781891d58']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        '4034255a2c' => $representations['1.1']['Theater']['4034255a2c']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // Changelog with public-only parsed docs with matched.
            'changelog-public-docs-with-matched-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:BUY_TICKETS',
                    'tag:FEATURE_FLAG'
                ],
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            'e7dc298139' => $actions['1.1.3']['/movies/{id}']['error']['e7dc298139'],
                                            '162944fa14' => $actions['1.1.3']['/movies/{id}']['error']['162944fa14']
                                        ],
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            '162944fa14' => $actions['1.1.3']['/movies/{id}']['return']['162944fa14']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            '3781891d58' => $actions['1.1.3']['/movies']['return']['3781891d58']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Movie' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        'ba8ac44626' => $representations['1.1.3']['Movie']['ba8ac44626']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01'
                        ],
                        'changed' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/movies/{id}']['content_type']['979fc6e97f'],
                                            'f4628f751a' =>
                                                $actions['1.1.2']['/movies/{id}']['content_type']['f4628f751a']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/movies']['content_type']['979fc6e97f'],
                                            '066564ef49' =>
                                                $actions['1.1.2']['/movies']['content_type']['066564ef49']
                                        ]
                                    ]
                                ],
                                'Theaters' => [
                                    '/theaters/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/theaters/{id}']['content_type']['979fc6e97f'],
                                            'f4628f751a' =>
                                                $actions['1.1.2']['/theaters/{id}']['content_type']['f4628f751a']
                                        ]
                                    ],
                                    '/theaters' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/theaters']['content_type']['979fc6e97f'],
                                            '066564ef49' =>
                                                $actions['1.1.2']['/theaters']['content_type']['066564ef49']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'resources' => [
                                'Theaters' => [
                                    '/theaters/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            'b3a16c4d74' =>
                                                $actions['1.1.2']['/theaters/{id}']['action_error']['b3a16c4d74']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_PARAM => [
                                            '162944fa14' => $actions['1.1.1']['/movies/{id}']['param']['162944fa14']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01'
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        'ba8ac44626' => $representations['1.1']['Movie']['ba8ac44626']
                                    ]
                                ]
                            ],
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION => [
                                            'd81e7058dd' => call_user_func(
                                                function () use ($actions): array {
                                                    $actions = $actions['1.1']['/movies/{id}']['action']['d81e7058dd'];

                                                    // Remove the `DELETE` method from `/movies/{id}`, since that
                                                    // shouldn't be available under these conditions.
                                                    unset($actions[1]);
                                                    return $actions;
                                                }
                                            )
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_PARAM => [
                                            '776d02bb83' => $actions['1.1']['/movies']['param']['776d02bb83'],
                                            '3781891d58' => $actions['1.1']['/movies']['param']['3781891d58']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        '4034255a2c' => $representations['1.1']['Theater']['4034255a2c']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],

            // Changelog with public-only parsed docs.
            'changelog-public-docs' => [
                'private_objects' => false,
                'vendor_tags' => [],
                'expected' => [
                    '1.1.3' => [
                        '_details' => [
                            'release_date' => '2017-05-27',
                            'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
                        ],
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            'e7dc298139' => $actions['1.1.3']['/movies/{id}']['error']['e7dc298139'],
                                            '162944fa14' => $actions['1.1.3']['/movies/{id}']['error']['162944fa14']
                                        ],
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            '162944fa14' => $actions['1.1.3']['/movies/{id}']['return']['162944fa14']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_RETURN => [
                                            '3781891d58' => $actions['1.1.3']['/movies']['return']['3781891d58']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1.2' => [
                        '_details' => [
                            'release_date' => '2017-04-01',
                        ],
                        'changed' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/movies/{id}']['content_type']['979fc6e97f'],
                                            'f4628f751a' =>
                                                $actions['1.1.2']['/movies/{id}']['content_type']['f4628f751a']
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' => $actions['1.1.2']['/movies']['content_type']['979fc6e97f'],
                                            '066564ef49' => $actions['1.1.2']['/movies']['content_type']['066564ef49']
                                        ]
                                    ]
                                ],
                                'Theaters' => [
                                    '/theaters/{id}' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/theaters/{id}']['content_type']['979fc6e97f'],
                                            'f4628f751a' =>
                                                $actions['1.1.2']['/theaters/{id}']['content_type']['f4628f751a']
                                        ]
                                    ],
                                    '/theaters' => [
                                        Changelog::CHANGESET_TYPE_CONTENT_TYPE => [
                                            '979fc6e97f' =>
                                                $actions['1.1.2']['/theaters']['content_type']['979fc6e97f'],
                                            '066564ef49' =>
                                                $actions['1.1.2']['/theaters']['content_type']['066564ef49']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'resources' => [
                                'Theaters' => [
                                    '/theaters/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_ERROR => [
                                            'b3a16c4d74' =>
                                                $actions['1.1.2']['/theaters/{id}']['action_error']['b3a16c4d74']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        '_details' => [
                            'release_date' => '2017-03-01'
                        ],
                        'added' => [
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION_PARAM => [
                                            '162944fa14' => $actions['1.1.1']['/movies/{id}']['param']['162944fa14']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '1.1' => [
                        '_details' => [
                            'release_date' => '2017-02-01',
                        ],
                        'added' => [
                            'representations' => [
                                'Movie' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        'ba8ac44626' => call_user_func(
                                            function () use ($representations): array {
                                                $representation = $representations['1.1']['Movie']['ba8ac44626'];

                                                // Remove `external_urls.tickets` since it's private documentation and
                                                // shouldn't be available under these conditions.
                                                $representation[2] = $representation[3];
                                                unset($representation[3]);
                                                return $representation;
                                            }
                                        )
                                    ]
                                ]
                            ],
                            'resources' => [
                                'Movies' => [
                                    '/movies/{id}' => [
                                        Changelog::CHANGESET_TYPE_ACTION => [
                                            'd81e7058dd' => call_user_func(
                                                function () use ($actions): array {
                                                    $hash = 'd81e7058ddfce86beb09ddb2a2461ea16d949637';
                                                    $actions = $actions['1.1']['/movies/{id}']['action']['d81e7058dd'];

                                                    // Remove the `DELETE` method from `/movies/{id}`, since that
                                                    // shouldn't be available under these conditions.
                                                    unset($actions[1]);
                                                    return $actions;
                                                }
                                            )
                                        ]
                                    ],
                                    '/movies' => [
                                        Changelog::CHANGESET_TYPE_ACTION_PARAM => [
                                            '776d02bb83' => $actions['1.1']['/movies']['param']['776d02bb83'],
                                            '3781891d58' => $actions['1.1']['/movies']['param']['3781891d58']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'removed' => [
                            'representations' => [
                                'Theater' => [
                                    Changelog::CHANGESET_TYPE_REPRESENTATION_DATA => [
                                        '4034255a2c' => $representations['1.1']['Theater']['4034255a2c']
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
