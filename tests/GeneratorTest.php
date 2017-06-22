<?php
namespace Mill\Tests;

use Mill\Generator;
use Mill\Parser\Resource\Action\Documentation;
use Mill\Parser\Version;

class GeneratorTest extends TestCase
{
    public function testGeneratorParsesAllVersions()
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

    public function testGeneratorButExcludeARepresentation()
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
     * @param array $expected_representations
     * @param array $expected_resources
     * @return void
     */
    public function testGeneratorWithVersion($version, array $expected_representations, array $expected_resources)
    {
        $version_obj = new Version($version, __CLASS__, __METHOD__);
        $generator = new Generator($this->getConfig(), $version_obj);
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
                $this->assertCount(
                    count($expected['actions.data']),
                    $actual_resource['actions'],
                    $group . ' does not have the right amount of actions.'
                );

                /** @var Documentation $action */
                foreach ($actual_resource['actions'] as $i => $action) {
                    $this->assertInstanceOf('\Mill\Parser\Resource\Action\Documentation', $action);

                    // We don't need to test every facet of the generated MethodDocumentation, because we're doing
                    // that in other tests, we just want to make sure that these actions were grouped and compiled
                    // properly.
                    $annotations = $action->toArray()['annotations'];
                    $expected_action = $expected['actions.data'][$i];

                    $this->assertSame($expected_action['method'], $action->getMethod());
                    $this->assertSame($annotations['uri'][0]['path'], $expected_action['uri']);
                    $this->assertSame(
                        $annotations['uri'][0]['visible'],
                        $expected_action['uri.visible'],
                        $expected_action['uri'] . '::' . $expected_action['method']
                    );

                    if ($expected_action['uriSegment'] === false) {
                        $this->assertArrayNotHasKey(
                            'uriSegment',
                            $annotations,
                            $expected_action['uri'] . ' has a segment'
                        );
                    } else {
                        $this->assertSame($annotations['uriSegment'], $expected_action['uriSegment']);
                    }

                    $this->assertSame(
                        $expected_action['params.keys'],
                        array_keys($action->getParameters()),
                        $i . ' does not have the right parameters.'
                    );
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

    /**
     * @return array
     */
    public function providerGeneratorWithVersion()
    {
        // Save us the effort of copy and pasting the same base actions over and over.
        $actions = [
            '/movie/+id::GET' => [
                'uri' => '/movie/+id',
                'method' => 'GET',
                'uriSegment' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'uri' => '/movie/+id',
                        'values' => false
                    ]
                ],
                'uri.visible' => false,
                'params.keys' => []
            ],
            '/movies::GET' => [
                'uri' => '/movies',
                'method' => 'GET',
                'uriSegment' => false,
                'uri.visible' => true,
                'params.keys' => [
                    'location'
                ]
            ],
            '/movies::POST' => [
                'uri' => '/movies',
                'method' => 'POST',
                'uriSegment' => false,
                'uri.visible' => true,
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
                ]
            ],
            '/movies/+id::GET' => [
                'uri' => '/movies/+id',
                'method' => 'GET',
                'uriSegment' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'uri' => '/movies/+id',
                        'values' => false
                    ]
                ],
                'uri.visible' => true,
                'params.keys' => []
            ],
            '/movies/+id::PATCH' => [
                'uri' => '/movies/+id',
                'method' => 'PATCH',
                'uriSegment' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'uri' => '/movies/+id',
                        'values' => false
                    ]
                ],
                'uri.visible' => true,
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
                ]
            ],
            '/movies/+id::DELETE' => [
                'uri' => '/movies/+id',
                'method' => 'DELETE',
                'uriSegment' => [
                    [
                        'description' => 'Movie ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'uri' => '/movies/+id',
                        'values' => false
                    ]
                ],
                'uri.visible' => false,
                'params.keys' => []
            ],
            '/theaters::GET' => [
                'uri' => '/theaters',
                'method' => 'GET',
                'uriSegment' => false,
                'uri.visible' => true,
                'params.keys' => [
                    'location'
                ]
            ],
            '/theaters::POST' => [
                'uri' => '/theaters',
                'method' => 'POST',
                'uriSegment' => false,
                'uri.visible' => true,
                'params.keys' => [
                    'address',
                    'name',
                    'phone_number'
                ]
            ],
            '/theaters/+id::GET' => [
                'uri' => '/theaters/+id',
                'method' => 'GET',
                'uriSegment' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'uri' => '/theaters/+id',
                        'values' => false
                    ]
                ],
                'uri.visible' => true,
                'params.keys' => []
            ],
            '/theaters/+id::PATCH' => [
                'uri' => '/theaters/+id',
                'method' => 'PATCH',
                'uriSegment' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'uri' => '/theaters/+id',
                        'values' => false
                    ]
                ],
                'uri.visible' => true,
                'params.keys' => [
                    'address',
                    'name',
                    'phone_number'
                ]
            ],
            '/theaters/+id::DELETE' => [
                'uri' => '/theaters/+id',
                'method' => 'DELETE',
                'uriSegment' => [
                    [
                        'description' => 'Theater ID',
                        'field' => 'id',
                        'type' => 'integer',
                        'uri' => '/theaters/+id',
                        'values' => false
                    ]
                ],
                'uri.visible' => false,
                'params.keys' => []
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
            'version-1.0' => [
                'version' => '1.0',
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => $representations['Movie'],
                    '\Mill\Examples\Showtimes\Representations\Person' => $representations['Person'],
                    '\Mill\Examples\Showtimes\Representations\Theater' => call_user_func(
                        function () use ($representations) {
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
                                    '/movies::GET' => $actions['/movies::GET'],
                                    '/movies::POST' => $actions['/movies::POST'],
                                    '/movies/+id::GET' => $actions['/movies/+id::GET']
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
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST'],
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'version-1.1' => [
                'version' => '1.1',
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => call_user_func(
                        function () use ($representations) {
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
                                    '/movies::GET' => call_user_func(function () use ($actions) {
                                        $action = $actions['/movies::GET'];
                                        $action['params.keys'][] = 'page';

                                        return $action;
                                    }),
                                    '/movies::POST' => call_user_func(function () use ($actions) {
                                        $action = $actions['/movies::POST'];
                                        $action['params.keys'][] = 'imdb';
                                        $action['params.keys'][] = 'trailer';

                                        sort($action['params.keys']);

                                        return $action;
                                    }),
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies/+id::PATCH' => $actions['/movies/+id::PATCH'],
                                    '/movies/+id::DELETE' => $actions['/movies/+id::DELETE']
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
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST'],
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'version-1.1.1' => [
                'version' => '1.1.1',
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => call_user_func(
                        function () use ($representations) {
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
                                    '/movies::GET' => call_user_func(function () use ($actions) {
                                        $action = $actions['/movies::GET'];
                                        $action['params.keys'][] = 'page';

                                        return $action;
                                    }),
                                    '/movies::POST' => call_user_func(function () use ($actions) {
                                        $action = $actions['/movies::POST'];
                                        $action['params.keys'][] = 'imdb';
                                        $action['params.keys'][] = 'trailer';

                                        sort($action['params.keys']);

                                        return $action;
                                    }),
                                    '/movies/+id::GET' => $actions['/movies/+id::GET'],
                                    '/movies/+id::PATCH' => call_user_func(function () use ($actions) {
                                        $action = $actions['/movies/+id::PATCH'];
                                        $action['params.keys'][] = 'imdb';

                                        sort($action['params.keys']);

                                        return $action;
                                    }),
                                    '/movies/+id::DELETE' => $actions['/movies/+id::DELETE']
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
                                    '/theaters::GET' => $actions['/theaters::GET'],
                                    '/theaters::POST' => $actions['/theaters::POST'],
                                    '/theaters/+id::GET' => $actions['/theaters/+id::GET'],
                                    '/theaters/+id::PATCH' => $actions['/theaters/+id::PATCH'],
                                    '/theaters/+id::DELETE' => $actions['/theaters/+id::DELETE']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
