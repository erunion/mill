<?php
namespace Mill\Tests;

use Mill\Generator;
use Mill\Parser\Resource\Action\Documentation;
use Mill\Parser\Version;

/**
 * This is the main generator system unit test. It's a bit... complex.
 *
 */
class GeneratorTest extends TestCase
{
    public function testGeneratorParsesAllVersions(): void
    {
        $generator = new Generator($this->getConfig());
        $generator->generate();

        $this->assertSame([
            '1.0',
            '1.1',
            '1.1.1',
            '1.1.2',
            '1.1.3'
        ], array_keys($generator->getResources()));
    }

    public function testGeneratorButExcludeARepresentation(): void
    {
        $config = $this->getConfig();
        $config->addExcludedRepresentation('\Mill\Examples\Showtimes\Representations\Movie');

        $version_obj = new Version('1.0', __CLASS__, __METHOD__);
        $generator = new Generator($config, $version_obj);
        $generator->generate();

        $this->assertSame([
            '\Mill\Examples\Showtimes\Representations\CodedError',
            '\Mill\Examples\Showtimes\Representations\Error',
            '\Mill\Examples\Showtimes\Representations\Person',
            '\Mill\Examples\Showtimes\Representations\Theater'
        ], array_keys($generator->getRepresentations($version_obj->getConstraint())));

        // Clean up after ourselves.
        $config->removeExcludedRepresentation('\Mill\Examples\Showtimes\Representations\Movie');
    }

    /**
     * @dataProvider providerGeneratorWithVersion
     * @param string $version
     * @param bool $private_objects
     * @param array|null $vendor_tags
     * @param array $expected_representations
     * @param array $expected_resources
     */
    public function testGeneratorWithVersion(
        string $version,
        bool $private_objects,
        ?array $vendor_tags,
        array $expected_representations,
        array $expected_resources
    ): void {
        $version_obj = new Version($version, __CLASS__, __METHOD__);
        $generator = new Generator($this->getConfig(), $version_obj);
        $generator->setLoadPrivateDocs($private_objects);
        $generator->setLoadVendorTagDocs($vendor_tags);
        $generator->generate();

        // Verify resources
        $resources = $generator->getResources();
        $this->assertArrayHasKey($version, $resources);

        $resources = $resources[$version];
        $this->assertSame($resources, $generator->getResources($version));
        foreach ($expected_resources as $group => $data) {
            $this->assertArrayHasKey($group, $resources);

            $actual = $resources[$group];

            $this->assertCount(
                count($data['resources']),
                $actual['resources'],
                $group . ' was not compiled with the right amount of resources.'
            );

            foreach ($data['resources'] as $expected) {
                $resource_name = $expected['resource.name'];

                $this->assertArrayHasKey($resource_name, $actual['resources']);
                $actual_resource = $actual['resources'][$resource_name];

                $this->assertSame($expected['resource.name'], $actual_resource['label']);
                $this->assertSame($expected['description.length'], strlen($actual_resource['description']));

                $this->assertSame(array_keys($expected['actions.data']), array_keys($actual_resource['actions']));
                $this->assertCount(
                    count($expected['actions.data']),
                    $actual_resource['actions'],
                    $group . ' does not have the right amount of actions.'
                );

                /** @var Documentation $action */
                foreach ($actual_resource['actions'] as $identifier => $action) {
                    $this->assertInstanceOf(Documentation::class, $action);

                    // We don't need to test every facet of the generated MethodDocumentation, because we're doing
                    // that in other tests, we just want to make sure that these actions were grouped and compiled
                    // properly.
                    $annotations = $action->toArray()['annotations'];
                    $expected_action = $expected['actions.data'][$identifier];

                    $this->assertSame($expected_action['method'], $action->getMethod());
                    $this->assertSame($annotations['path'][0]['path'], $expected_action['path']);
                    $this->assertSame(
                        $annotations['path'][0]['visible'],
                        $expected_action['path.visible'],
                        $expected_action['path'] . '::' . $expected_action['method']
                    );

                    if ($expected_action['pathParam'] === false) {
                        $this->assertArrayNotHasKey(
                            'pathParam',
                            $annotations,
                            $expected_action['path'] . ' has a path param'
                        );
                    } else {
                        $this->assertSame($annotations['pathParam'], $expected_action['pathParam']);
                    }

                    $this->assertSame(
                        $expected_action['params.keys'],
                        array_keys($action->getParameters()),
                        $identifier . ' does not have the right parameters.'
                    );

                    // Verify that we've generated the right public/private/protected annotations for this action.
                    ksort($annotations);
                    $this->assertSame(
                        array_keys($expected_action['annotations.sum']),
                        array_keys($annotations),
                        $identifier . ' is missing annotations.'
                    );

                    foreach ($expected_action['annotations.sum'] as $name => $sum) {
                        $this->assertCount(
                            $sum,
                            $annotations[$name],
                            $identifier . ' does not have the right amount of `' . $name . '` annotations.'
                        );
                    }
                }
            }
        }

        // Verify representations
        $representations = $generator->getRepresentations();
        $this->assertArrayHasKey($version, $representations);

        $representations = $representations[$version];
        $this->assertCount(count($expected_representations), $representations);
        $this->assertSame($representations, $generator->getRepresentations($version_obj));

        foreach ($expected_representations as $name => $data) {
            $this->assertArrayHasKey($name, $representations);

            /** @var \Mill\Parser\Representation\Documentation $representation */
            $representation = $representations[$name];
            $actual = $representation->toArray();

            $this->assertSame($data['label'], $actual['label']);
            $this->assertSame($data['description.length'], strlen($actual['description']));
            $this->assertSame($data['content.keys'], array_keys($actual['content']));
            $this->assertSame($data['content.keys'], array_keys($representation->getContent()));
        }
    }

    public function providerGeneratorWithVersion(): array
    {
        // Save us the effort of copy and pasting the same base actions over and over.
        $actions = [
            '/movie/+id::GET' => [
                'path' => '/movie/+id',
                'method' => 'GET',
                'pathParam' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => false,
                'params.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'pathParam' => 1,
                    'return' => 2
                ]
            ],
            '/movies::GET' => [
                'path' => '/movies',
                'method' => 'GET',
                'pathParam' => false,
                'path.visible' => true,
                'params.keys' => [
                    'location'
                ],
                'annotations.sum' => [
                    'error' => 1,
                    'param' => 1,
                    'path' => 1,
                    'return' => 1
                ]
            ],
            '/movies::POST' => [
                'path' => '/movies',
                'method' => 'POST',
                'pathParam' => false,
                'path.visible' => true,
                'params.keys' => [
                    'cast',
                    'content_rating',
                    'description',
                    'director',
                    'genres',
                    'is_kid_friendly',
                    'name',
                    'rotten_tomatoes_score',
                    'runtime'
                ],
                'annotations.sum' => [
                    'error' => 2,
                    'param' => 9,
                    'path' => 1,
                    'return' => 1,
                    'scope' => 1
                ]
            ],
            '/movies/+id::GET' => [
                'path' => '/movies/+id',
                'method' => 'GET',
                'pathParam' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => true,
                'params.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'pathParam' => 1,
                    'return' => 2
                ]
            ],
            '/movies/+id::PATCH' => [
                'path' => '/movies/+id',
                'method' => 'PATCH',
                'pathParam' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => true,
                'params.keys' => [
                    'cast',
                    'content_rating',
                    'description',
                    'director',
                    'genres',
                    'is_kid_friendly',
                    'name',
                    'rotten_tomatoes_score',
                    'runtime',
                    'trailer'
                ],
                'annotations.sum' => [
                    'error' => 3,
                    'minVersion' => 1,
                    'param' => 10,
                    'path' => 1,
                    'pathParam' => 1,
                    'return' => 1,
                    'scope' => 1
                ]
            ],
            '/movies/+id::DELETE' => [
                'path' => '/movies/+id',
                'method' => 'DELETE',
                'pathParam' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => false,
                'params.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'minVersion' => 1,
                    'path' => 1,
                    'pathParam' => 1,
                    'return' => 1,
                    'scope' => 1,
                    'vendorTag' => 1
                ]
            ],
            '/theaters::GET' => [
                'path' => '/theaters',
                'method' => 'GET',
                'pathParam' => false,
                'path.visible' => true,
                'params.keys' => [
                    'location'
                ],
                'annotations.sum' => [
                    'error' => 1,
                    'param' => 1,
                    'path' => 1,
                    'return' => 1
                ]
            ],
            '/theaters::POST' => [
                'path' => '/theaters',
                'method' => 'POST',
                'pathParam' => false,
                'path.visible' => true,
                'params.keys' => [
                    'address',
                    'name',
                    'phone_number'
                ],
                'annotations.sum' => [
                    'error' => 1,
                    'param' => 3,
                    'path' => 1,
                    'return' => 1,
                    'scope' => 1
                ]
            ],
            '/theaters/+id::GET' => [
                'path' => '/theaters/+id',
                'method' => 'GET',
                'pathParam' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => true,
                'params.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'pathParam' => 1,
                    'return' => 2
                ]
            ],
            '/theaters/+id::PATCH' => [
                'path' => '/theaters/+id',
                'method' => 'PATCH',
                'pathParam' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => true,
                'params.keys' => [
                    'address',
                    'name',
                    'phone_number'
                ],
                'annotations.sum' => [
                    'error' => 3,
                    'param' => 3,
                    'path' => 1,
                    'pathParam' => 1,
                    'return' => 1,
                    'scope' => 1
                ]
            ],
            '/theaters/+id::DELETE' => [
                'path' => '/theaters/+id',
                'method' => 'DELETE',
                'pathParam' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => false,
                'params.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'pathParam' => 1,
                    'return' => 1,
                    'scope' => 1
                ]
            ]
        ];

        $representations = [
            'Movie' => [
                'label' => 'Movie',
                'description.length' => 41,
                'content.keys' => [
                    'cast',
                    'content_rating',
                    'description',
                    'director',
                    'genres',
                    'id',
                    'kid_friendly',
                    'name',
                    'purchase.url',
                    'rotten_tomatoes_score',
                    'runtime',
                    'showtimes',
                    'theaters',
                    'uri'
                ]
            ],
            'Person' => [
                'label' => 'Person',
                'description.length' => 42,
                'content.keys' => [
                    'id',
                    'imdb',
                    'name',
                    'uri'
                ]
            ],
            'Theater' => [
                'label' => 'Theater',
                'description.length' => 49,
                'content.keys' => [
                    'address',
                    'id',
                    'movies',
                    'name',
                    'phone_number',
                    'showtimes',
                    'uri',
                ]
            ]
        ];

        $error_representations = [
            '\Mill\Examples\Showtimes\Representations\CodedError' => [
                'label' => 'Coded error',
                'description.length' => 0,
                'content.keys' => [
                    'error',
                    'error_code'
                ]
            ],
            '\Mill\Examples\Showtimes\Representations\Error' => [
                'label' => 'Error',
                'description.length' => 0,
                'content.keys' => [
                    'error'
                ]
            ],
        ];

        return [
            // API v1.0 with all documentation
            'version-1.0' => [
                'version' => '1.0',
                'private_objects' => true,
                'vendor_tags' => null,
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => $representations['Movie'],
                    '\Mill\Examples\Showtimes\Representations\Person' => $representations['Person'],
                    '\Mill\Examples\Showtimes\Representations\Theater' => call_user_func(
                        function () use ($representations): array {
                            $representation = $representations['Theater'];
                            $representation['content.keys'] = array_merge($representation['content.keys'], [
                                'website'
                            ]);

                            sort($representation['content.keys']);
                            return $representation;
                        }
                    )
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 103,
                                'actions.data' => [
                                    '/movie/+id::GET' => $actions['/movie/+id::GET'],
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies::GET' => $actions['/movies::GET'],
                                    '/movies::POST' => $actions['/movies::POST']
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 119,
                                'actions.data' => [
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE'],
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST']
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // API v1.0 with public-only docs and all vendor tags.
            'version-1.0-public-docs-with-all-vendor-tags' => [
                'version' => '1.0',
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:BUY_TICKETS',
                    'tag:DELETE_CONTENT',
                    'tag:FEATURE_FLAG',
                    'tag:MOVIE_RATINGS'
                ],
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => $representations['Movie'],
                    '\Mill\Examples\Showtimes\Representations\Person' => $representations['Person'],
                    '\Mill\Examples\Showtimes\Representations\Theater' => call_user_func(
                        function () use ($representations): array {
                            $representation = $representations['Theater'];
                            $representation['content.keys'] = array_merge($representation['content.keys'], [
                                'website'
                            ]);

                            sort($representation['content.keys']);
                            return $representation;
                        }
                    )
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 103,
                                'actions.data' => [
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies::GET' => $actions['/movies::GET'],
                                    '/movies::POST' => $actions['/movies::POST']
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 119,
                                'actions.data' => [
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST']
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // API v1.1 with all documentation
            'version-1.1' => [
                'version' => '1.1',
                'private_objects' => true,
                'vendor_tags' => null,
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => call_user_func(
                        function () use ($representations): array {
                            $representation = $representations['Movie'];
                            $representation['content.keys'] = array_merge($representation['content.keys'], [
                                'external_urls',
                                'external_urls.imdb',
                                'external_urls.tickets',
                                'external_urls.trailer'
                            ]);

                            sort($representation['content.keys']);
                            return $representation;
                        }
                    ),
                    '\Mill\Examples\Showtimes\Representations\Person' => $representations['Person'],
                    '\Mill\Examples\Showtimes\Representations\Theater' => $representations['Theater']
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 103,
                                'actions.data' => [
                                    '/movie/+id::GET' => $actions['/movie/+id::GET'],
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies/+id::PATCH' => $actions['/movies/+id::PATCH'],
                                    '/movies/+id::DELETE' => $actions['/movies/+id::DELETE'],
                                    '/movies::GET' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::GET'];
                                        $action['params.keys'][] = 'page';
                                        $action['annotations.sum']['param']++;

                                        return $action;
                                    }),
                                    '/movies::POST' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::POST'];
                                        $action['params.keys'][] = 'imdb';
                                        $action['params.keys'][] = 'trailer';

                                        $action['annotations.sum']['param']++;
                                        $action['annotations.sum']['param']++;

                                        sort($action['params.keys']);

                                        return $action;
                                    })
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 119,
                                'actions.data' => [
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE'],
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST']
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // API v1.1 with public-only docs and unmatched vendor tags.
            'version-1.1-public-docs-with-unmatched-vendor-tags' => [
                'version' => '1.1',
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:BUY_TICKETS',
                    'tag:FEATURE_FLAG'
                ],
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => call_user_func(
                        function () use ($representations): array {
                            $representation = $representations['Movie'];
                            $representation['content.keys'] = array_merge($representation['content.keys'], [
                                'external_urls',
                                'external_urls.imdb',
                                'external_urls.tickets',
                                'external_urls.trailer'
                            ]);

                            sort($representation['content.keys']);
                            return $representation;
                        }
                    ),
                    '\Mill\Examples\Showtimes\Representations\Person' => $representations['Person'],
                    '\Mill\Examples\Showtimes\Representations\Theater' => $representations['Theater']
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 103,
                                'actions.data' => [
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies/+id::PATCH' => $actions['/movies/+id::PATCH'],
                                    '/movies::GET' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::GET'];
                                        $action['params.keys'][] = 'page';
                                        $action['annotations.sum']['param']++;

                                        return $action;
                                    }),
                                    '/movies::POST' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::POST'];
                                        $action['params.keys'][] = 'imdb';
                                        $action['params.keys'][] = 'trailer';

                                        $action['annotations.sum']['param']++;
                                        $action['annotations.sum']['param']++;

                                        sort($action['params.keys']);

                                        return $action;
                                    })
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 119,
                                'actions.data' => [
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST']
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // API v1.1 with public-only docs and matched vendor tags.
            'version-1.1-public-docs-with-matched-vendor-tags' => [
                'version' => '1.1',
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:DELETE_CONTENT'
                ],
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => call_user_func(
                        function () use ($representations): array {
                            $representation = $representations['Movie'];
                            $representation['content.keys'] = array_merge($representation['content.keys'], [
                                'external_urls',
                                'external_urls.imdb',
                                'external_urls.trailer'
                            ]);

                            sort($representation['content.keys']);
                            return $representation;
                        }
                    ),
                    '\Mill\Examples\Showtimes\Representations\Person' => $representations['Person'],
                    '\Mill\Examples\Showtimes\Representations\Theater' => $representations['Theater']
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 103,
                                'actions.data' => [
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies/+id::PATCH' => $actions['/movies/+id::PATCH'],
                                    '/movies/+id::DELETE' => $actions['/movies/+id::DELETE'],
                                    '/movies::GET' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::GET'];
                                        $action['params.keys'][] = 'page';
                                        $action['annotations.sum']['param']++;

                                        return $action;
                                    }),
                                    '/movies::POST' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::POST'];
                                        $action['params.keys'][] = 'imdb';
                                        $action['params.keys'][] = 'trailer';

                                        $action['annotations.sum']['param']++;
                                        $action['annotations.sum']['param']++;

                                        sort($action['params.keys']);

                                        return $action;
                                    }),
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 119,
                                'actions.data' => [
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST']
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // API v1.1.1 with all documentation
            'version-1.1.1' => [
                'version' => '1.1.1',
                'private_objects' => true,
                'vendor_tags' => null,
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => call_user_func(
                        function () use ($representations): array {
                            $representation = $representations['Movie'];
                            $representation['content.keys'] = array_merge($representation['content.keys'], [
                                'external_urls',
                                'external_urls.imdb',
                                'external_urls.tickets',
                                'external_urls.trailer'
                            ]);

                            sort($representation['content.keys']);
                            return $representation;
                        }
                    ),
                    '\Mill\Examples\Showtimes\Representations\Person' => $representations['Person'],
                    '\Mill\Examples\Showtimes\Representations\Theater' => $representations['Theater']
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 103,
                                'actions.data' => [
                                    '/movie/+id::GET' => $actions['/movie/+id::GET'],
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies/+id::PATCH' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies/+id::PATCH'];
                                        $action['params.keys'][] = 'imdb';

                                        $action['annotations.sum']['param']++;

                                        sort($action['params.keys']);

                                        return $action;
                                    }),
                                    '/movies/+id::DELETE' => $actions['/movies/+id::DELETE'],
                                    '/movies::GET' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::GET'];
                                        $action['params.keys'][] = 'page';
                                        $action['annotations.sum']['param']++;

                                        return $action;
                                    }),
                                    '/movies::POST' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::POST'];
                                        $action['params.keys'][] = 'imdb';
                                        $action['params.keys'][] = 'trailer';

                                        $action['annotations.sum']['param']++;
                                        $action['annotations.sum']['param']++;

                                        sort($action['params.keys']);

                                        return $action;
                                    })
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 119,
                                'actions.data' => [
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE'],
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST']
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // API v1.1.1 with public-only documentation
            'version-1.1.1-public-only-documentation' => [
                'version' => '1.1.1',
                'private_objects' => true,
                'vendor_tags' => [],
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => call_user_func(
                        function () use ($representations): array {
                            $representation = $representations['Movie'];
                            $representation['content.keys'] = array_merge($representation['content.keys'], [
                                'external_urls',
                                'external_urls.imdb',
                                'external_urls.trailer'
                            ]);

                            sort($representation['content.keys']);
                            return $representation;
                        }
                    ),
                    '\Mill\Examples\Showtimes\Representations\Person' => $representations['Person'],
                    '\Mill\Examples\Showtimes\Representations\Theater' => $representations['Theater']
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 103,
                                'actions.data' => [
                                    '/movie/+id::GET' => $actions['/movie/+id::GET'],
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies/+id::PATCH' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies/+id::PATCH'];
                                        $action['params.keys'][] = 'imdb';

                                        $action['annotations.sum']['param']++;

                                        sort($action['params.keys']);

                                        return $action;
                                    }),
                                    '/movies::GET' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::GET'];
                                        $action['params.keys'][] = 'page';
                                        $action['annotations.sum']['param']++;

                                        return $action;
                                    }),
                                    '/movies::POST' => call_user_func(function () use ($actions): array {
                                        $action = $actions['/movies::POST'];
                                        $action['params.keys'][] = 'imdb';
                                        $action['params.keys'][] = 'trailer';

                                        $action['annotations.sum']['param']++;
                                        $action['annotations.sum']['param']++;

                                        sort($action['params.keys']);

                                        return $action;
                                    })
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 119,
                                'actions.data' => [
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE'],
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
