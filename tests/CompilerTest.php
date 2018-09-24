<?php
namespace Mill\Tests;

use Mill\Compiler;
use Mill\Parser\Resource\Action\Documentation;
use Mill\Parser\Version;

class CompilerTest extends TestCase
{
    public function testParsesAllVersions(): void
    {
        $compiler = new Compiler($this->getConfig());
        $compiler->compile();

        $this->assertSame([
            '1.0',
            '1.1',
            '1.1.1',
            '1.1.2',
            '1.1.3'
        ], array_keys($compiler->getResources()));
    }

    public function testExcludeARepresentation(): void
    {
        $config = $this->getConfig();
        $config->addExcludedRepresentation('\Mill\Examples\Showtimes\Representations\Movie');

        $version_obj = new Version('1.0', __CLASS__, __METHOD__);
        $compiler = new Compiler($config, $version_obj);
        $compiler->compile();

        $this->assertSame([
            '\Mill\Examples\Showtimes\Representations\CodedError',
            '\Mill\Examples\Showtimes\Representations\Error',
            '\Mill\Examples\Showtimes\Representations\Person',
            '\Mill\Examples\Showtimes\Representations\Theater'
        ], array_keys($compiler->getRepresentations($version_obj->getConstraint())));

        // Clean up after ourselves.
        $config->removeExcludedRepresentation('\Mill\Examples\Showtimes\Representations\Movie');
    }

    /**
     * @dataProvider providerWithVersion
     * @param string $version
     * @param bool $private_objects
     * @param array|null $vendor_tags
     * @param array $expected_representations
     * @param array $expected_resources
     */
    public function testWithVersion(
        string $version,
        bool $private_objects,
        ?array $vendor_tags,
        array $expected_representations,
        array $expected_resources
    ): void {
        $version_obj = new Version($version, __CLASS__, __METHOD__);
        $compiler = new Compiler($this->getConfig(), $version_obj);
        $compiler->setLoadPrivateDocs($private_objects);
        $compiler->setLoadVendorTagDocs($vendor_tags);
        $compiler->compile();

        // Verify resources
        $resources = $compiler->getResources();
        $this->assertArrayHasKey($version, $resources);

        $resources = $resources[$version];
        $this->assertSame($resources, $compiler->getResources($version));
        foreach ($expected_resources as $group => $expected_group) {
            $this->assertArrayHasKey($group, $resources);

            $actual_group = $resources[$group];

            $this->assertSame(array_keys($expected_group['actions']), array_keys($actual_group['actions']));
            $this->assertCount(
                count($expected_group['actions']),
                $actual_group['actions'],
                $group . ' was not compiled with the right amount of resource actions.'
            );

            foreach ($expected_group['actions'] as $expected) {
                $identifier = $expected['path'] . '::' . $expected['method'];
                $this->assertArrayHasKey($identifier, $actual_group['actions']);

                /** @var Documentation $actual */
                $actual = $actual_group['actions'][$identifier];
                $this->assertInstanceOf(Documentation::class, $actual);

                // We don't need to test every facet of the compiled MethodDocumentation, because we're doing that in
                // other tests, we just want to make sure that these actions were grouped and compiled properly.
                $annotations = $actual->toArray()['annotations'];

                $this->assertSame($expected['method'], $actual->getMethod());
                $this->assertSame($annotations['path'][0]['path'], $expected['path']);
                $this->assertSame(
                    $annotations['path'][0]['visible'],
                    $expected['path.visible'],
                    $expected['path'] . '::' . $expected['method']
                );

                if ($expected['pathparam'] === false) {
                    $this->assertArrayNotHasKey('pathparam', $annotations, $expected['path'] . ' has a path param');
                } else {
                    $this->assertSame($annotations['pathparam'], $expected['pathparam']);
                }

                $this->assertSame(
                    $expected['params.keys'],
                    array_keys($actual->getParameters()),
                    $identifier . ' does not have the right parameters.'
                );

                $this->assertSame(
                    $expected['queryparams.keys'],
                    array_keys($actual->getQueryParameters()),
                    $identifier . ' does not have the right query parameters.'
                );

                // Verify that we've compiled the right public/private/protected annotations for this action.
                ksort($annotations);
                $this->assertSame(
                    array_keys($expected['annotations.sum']),
                    array_keys($annotations),
                    $identifier . ' is missing annotations.'
                );

                foreach ($expected['annotations.sum'] as $name => $sum) {
                    $this->assertCount(
                        $sum,
                        $annotations[$name],
                        $identifier . ' does not have the right amount of `' . $name . '` annotations.'
                    );
                }
            }
        }

        // Verify representations
        $representations = $compiler->getRepresentations();
        $this->assertArrayHasKey($version, $representations);

        $representations = $representations[$version];
        $this->assertCount(count($expected_representations), $representations);
        $this->assertSame($representations, $compiler->getRepresentations($version_obj));

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

    public function providerWithVersion(): array
    {
        // Save us the effort of copy and pasting the same base actions over and over.
        $actions = [
            '/movie/+id::GET' => [
                'path' => '/movie/+id',
                'method' => 'GET',
                'pathparam' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'required' => true,
                        'sample_data' => '1234',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => false,
                'params.keys' => [],
                'queryparams.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'pathparam' => 1,
                    'return' => 2
                ]
            ],
            '/movies::GET' => [
                'path' => '/movies',
                'method' => 'GET',
                'pathparam' => false,
                'path.visible' => true,
                'params.keys' => [],
                'queryparams.keys' => [
                    'location'
                ],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'queryparam' => 1,
                    'return' => 1
                ]
            ],
            '/movies::POST' => [
                'path' => '/movies',
                'method' => 'POST',
                'pathparam' => false,
                'path.visible' => true,
                'params.keys' => [
                    'cast',
                    'cast.name',
                    'cast.role',
                    'content_rating',
                    'description',
                    'director',
                    'genres',
                    'is_kid_friendly',
                    'name',
                    'rotten_tomatoes_score',
                    'runtime'
                ],
                'queryparams.keys' => [],
                'annotations.sum' => [
                    'error' => 2,
                    'param' => 11,
                    'path' => 1,
                    'return' => 1,
                    'scope' => 1
                ]
            ],
            '/movies/+id::GET' => [
                'path' => '/movies/+id',
                'method' => 'GET',
                'pathparam' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'required' => true,
                        'sample_data' => '1234',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => true,
                'params.keys' => [],
                'queryparams.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'pathparam' => 1,
                    'return' => 2
                ]
            ],
            '/movies/+id::PATCH' => [
                'path' => '/movies/+id',
                'method' => 'PATCH',
                'pathparam' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'required' => true,
                        'sample_data' => '1234',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => true,
                'params.keys' => [
                    'cast',
                    'cast.name',
                    'cast.role',
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
                'queryparams.keys' => [],
                'annotations.sum' => [
                    'error' => 3,
                    'minversion' => 1,
                    'param' => 12,
                    'path' => 1,
                    'pathparam' => 1,
                    'return' => 1,
                    'scope' => 1
                ]
            ],
            '/movies/+id::DELETE' => [
                'path' => '/movies/+id',
                'method' => 'DELETE',
                'pathparam' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'required' => true,
                        'sample_data' => '1234',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => false,
                'params.keys' => [],
                'queryparams.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'maxversion' => 1,
                    'minversion' => 1,
                    'path' => 1,
                    'pathparam' => 1,
                    'return' => 1,
                    'scope' => 1,
                    'vendortag' => 1
                ]
            ],
            '/theaters::GET' => [
                'path' => '/theaters',
                'method' => 'GET',
                'pathparam' => false,
                'path.visible' => true,
                'params.keys' => [],
                'queryparams.keys' => [
                    'location'
                ],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'queryparam' => 1,
                    'return' => 1
                ]
            ],
            '/theaters::POST' => [
                'path' => '/theaters',
                'method' => 'POST',
                'pathparam' => false,
                'path.visible' => true,
                'params.keys' => [
                    'address',
                    'name',
                    'phone_number'
                ],
                'queryparams.keys' => [],
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
                'pathparam' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'required' => true,
                        'sample_data' => '1234',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => true,
                'params.keys' => [],
                'queryparams.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'pathparam' => 1,
                    'return' => 2
                ]
            ],
            '/theaters/+id::PATCH' => [
                'path' => '/theaters/+id',
                'method' => 'PATCH',
                'pathparam' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'required' => true,
                        'sample_data' => '1234',
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
                'queryparams.keys' => [],
                'annotations.sum' => [
                    'error' => 3,
                    'param' => 3,
                    'path' => 1,
                    'pathparam' => 1,
                    'return' => 1,
                    'scope' => 1
                ]
            ],
            '/theaters/+id::DELETE' => [
                'path' => '/theaters/+id',
                'method' => 'DELETE',
                'pathparam' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'required' => true,
                        'sample_data' => '1234',
                        'type' => 'integer',
                        'values' => []
                    ]
                ],
                'path.visible' => false,
                'params.keys' => [],
                'queryparams.keys' => [],
                'annotations.sum' => [
                    'error' => 1,
                    'path' => 1,
                    'pathparam' => 1,
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
                        'actions' => [
                            '/movie/+id::GET' => $actions['/movie/+id::GET'],
                            '/movies/+id::GET' => $actions['/movies/+id::GET'],
                            '/movies::GET' => $actions['/movies::GET'],
                            '/movies::POST' => $actions['/movies::POST']
                        ]
                    ],
                    'Theaters' => [
                        'actions' => [
                            '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                            '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                            '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE'],
                            '/theaters::GET' => $actions['/theaters::GET'],
                            '/theaters::POST' => $actions['/theaters::POST']
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
                        'actions' => [
                            '/movies/+id::GET' => $actions['/movies/+id::GET'],
                            '/movies::GET' => $actions['/movies::GET'],
                            '/movies::POST' => $actions['/movies::POST']
                        ]
                    ],
                    'Theaters' => [
                        'actions' => [
                            '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                            '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                            '/theaters::GET' => $actions['/theaters::GET'],
                            '/theaters::POST' => $actions['/theaters::POST']
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
                        'actions' => [
                            '/movie/+id::GET' => $actions['/movie/+id::GET'],
                            '/movies/+id::GET' => $actions['/movies/+id::GET'],
                            '/movies/+id::PATCH' => $actions['/movies/+id::PATCH'],
                            '/movies/+id::DELETE' => $actions['/movies/+id::DELETE'],
                            '/movies::GET' => call_user_func(function () use ($actions): array {
                                $action = $actions['/movies::GET'];
                                $action['queryparams.keys'][] = 'page';
                                $action['annotations.sum']['queryparam']++;

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
                    ],
                    'Theaters' => [
                        'actions' => [
                            '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                            '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                            '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE'],
                            '/theaters::GET' => $actions['/theaters::GET'],
                            '/theaters::POST' => $actions['/theaters::POST']
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
                        'actions' => [
                            '/movies/+id::GET' => $actions['/movies/+id::GET'],
                            '/movies/+id::PATCH' => $actions['/movies/+id::PATCH'],
                            '/movies::GET' => call_user_func(function () use ($actions): array {
                                $action = $actions['/movies::GET'];
                                $action['queryparams.keys'][] = 'page';
                                $action['annotations.sum']['queryparam']++;

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
                    ],
                    'Theaters' => [
                        'actions' => [
                            '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                            '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                            '/theaters::GET' => $actions['/theaters::GET'],
                            '/theaters::POST' => $actions['/theaters::POST']
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
                        'actions' => [
                            '/movies/+id::GET' => $actions['/movies/+id::GET'],
                            '/movies/+id::PATCH' => $actions['/movies/+id::PATCH'],
                            '/movies/+id::DELETE' => $actions['/movies/+id::DELETE'],
                            '/movies::GET' => call_user_func(function () use ($actions): array {
                                $action = $actions['/movies::GET'];
                                $action['queryparams.keys'][] = 'page';
                                $action['annotations.sum']['queryparam']++;

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
                    ],
                    'Theaters' => [
                        'actions' => [
                            '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                            '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                            '/theaters::GET' => $actions['/theaters::GET'],
                            '/theaters::POST' => $actions['/theaters::POST']
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
                        'actions' => [
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
                                $action['queryparams.keys'][] = 'page';
                                $action['annotations.sum']['queryparam']++;

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
                    ],
                    'Theaters' => [
                        'actions' => [
                            '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                            '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                            '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE'],
                            '/theaters::GET' => $actions['/theaters::GET'],
                            '/theaters::POST' => $actions['/theaters::POST']
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
                        'actions' => [
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
                                $action['queryparams.keys'][] = 'page';
                                $action['annotations.sum']['queryparam']++;

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
                    ],
                    'Theaters' => [
                        'actions' => [
                            '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                            '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                            '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE'],
                            '/theaters::GET' => $actions['/theaters::GET'],
                            '/theaters::POST' => $actions['/theaters::POST']
                        ]
                    ]
                ]
            ]
        ];
    }
}
