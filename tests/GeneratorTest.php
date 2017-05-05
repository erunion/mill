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
            '1.1.2'
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

        /**
         * Verify resources
         */
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

        /**
         * Verify representations
         */
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
        // Save us the effort of copy and pasting the same base endpoints over and over.
        $common_actions = [
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
                ]
            ],
            '/movies::GET' => [
                'uri' => '/movies',
                'method' => 'GET',
                'uriSegment' => false
            ],
            '/movies::POST' => [
                'uri' => '/movies',
                'method' => 'POST',
                'uriSegment' => false
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
                ]
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
                ]
            ],
            '/theaters::GET' => [
                'uri' => '/theaters',
                'method' => 'GET',
                'uriSegment' => false
            ],
            '/theaters::POST' => [
                'uri' => '/theaters',
                'method' => 'POST',
                'uriSegment' => false
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
                ]
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
                    '\Mill\Examples\Showtimes\Representations\Movie' => [
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
                            'rotten_tomatoes_score',
                            'runtime',
                            'showtimes',
                            'theaters',
                            'uri'
                        ]
                    ],
                    '\Mill\Examples\Showtimes\Representations\Person' => [
                        'label' => 'Person',
                        'description.length' => 42,
                        'content.keys' => [
                            'id',
                            'imdb',
                            'name',
                            'uri'
                        ]
                    ],
                    '\Mill\Examples\Showtimes\Representations\Theater' => [
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
                            'website'
                        ]
                    ]
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 32,
                                'actions.data' => [
                                    '/movie/+id::GET' => array_merge($common_actions['/movie/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/movies::GET' => array_merge($common_actions['/movies::GET'], [
                                        'params.keys' => [
                                            'location'
                                        ]
                                    ]),
                                    '/movies::POST' => array_merge($common_actions['/movies::POST'], [
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
                                    ]),
                                    '/movies/+id::GET' => array_merge($common_actions['/movies/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/movies/+id::DELETE' => array_merge($common_actions['/movies/+id::DELETE'], [
                                        'params.keys' => []
                                    ])
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 40,
                                'actions.data' => [
                                    '/theaters::GET' => array_merge($common_actions['/theaters::GET'], [
                                        'params.keys' => [
                                            'location'
                                        ]
                                    ]),
                                    '/theaters::POST' => array_merge($common_actions['/theaters::POST'], [
                                        'params.keys' => [
                                            'address',
                                            'name',
                                            'phone_number'
                                        ]
                                    ]),
                                    '/theaters/+id::GET' => array_merge($common_actions['/theaters/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/theaters/+id::PATCH' => array_merge($common_actions['/theaters/+id::PATCH'], [
                                        'params.keys' => [
                                            'address',
                                            'name',
                                            'phone_number'
                                        ]
                                    ]),
                                    '/theaters/+id::DELETE' => array_merge($common_actions['/theaters/+id::DELETE'], [
                                        'params.keys' => []
                                    ])
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            'version-1.1' => [
                'version' => '1.1',
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => [
                        'label' => 'Movie',
                        'description.length' => 41,
                        'content.keys' => [
                            'cast',
                            'content_rating',
                            'description',
                            'director',
                            'external_urls',
                            'external_urls.imdb',
                            'external_urls.tickets',
                            'external_urls.trailer',
                            'genres',
                            'id',
                            'kid_friendly',
                            'name',
                            'rotten_tomatoes_score',
                            'runtime',
                            'showtimes',
                            'theaters',
                            'uri'
                        ]
                    ],
                    '\Mill\Examples\Showtimes\Representations\Person' => [
                        'label' => 'Person',
                        'description.length' => 42,
                        'content.keys' => [
                            'id',
                            'imdb',
                            'name',
                            'uri'
                        ]
                    ],
                    '\Mill\Examples\Showtimes\Representations\Theater' => [
                        'label' => 'Theater',
                        'description.length' => 49,
                        'content.keys' => [
                            'address',
                            'id',
                            'movies',
                            'name',
                            'phone_number',
                            'showtimes',
                            'uri'
                        ]
                    ]
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 32,
                                'actions.data' => [
                                    '/movie/+id::GET' => array_merge($common_actions['/movie/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/movies::GET' => array_merge($common_actions['/movies::GET'], [
                                        'params.keys' => [
                                            'location'
                                        ]
                                    ]),
                                    '/movies::POST' => array_merge($common_actions['/movies::POST'], [
                                        'params.keys' => [
                                            'cast',
                                            'content_rating',
                                            'description',
                                            'director',
                                            'genres',
                                            'imdb',
                                            'is_kid_friendly',
                                            'name',
                                            'rotten_tomatoes_score',
                                            'runtime',
                                            'trailer'
                                        ]
                                    ]),
                                    '/movies/+id::GET' => array_merge($common_actions['/movies/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/movies/+id::PATCH' => array_merge($common_actions['/movies/+id::PATCH'], [
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
                                    ]),
                                    '/movies/+id::DELETE' => array_merge($common_actions['/movies/+id::DELETE'], [
                                        'params.keys' => []
                                    ])
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 40,
                                'actions.data' => [
                                    '/theaters::GET' => array_merge($common_actions['/theaters::GET'], [
                                        'params.keys' => [
                                            'location'
                                        ]
                                    ]),
                                    '/theaters::POST' => array_merge($common_actions['/theaters::POST'], [
                                        'params.keys' => [
                                            'address',
                                            'name',
                                            'phone_number'
                                        ]
                                    ]),
                                    '/theaters/+id::GET' => array_merge($common_actions['/theaters/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/theaters/+id::PATCH' => array_merge($common_actions['/theaters/+id::PATCH'], [
                                        'params.keys' => [
                                            'address',
                                            'name',
                                            'phone_number'
                                        ]
                                    ]),
                                    '/theaters/+id::DELETE' => array_merge($common_actions['/theaters/+id::DELETE'], [
                                        'params.keys' => []
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'version-1.1.1' => [
                'version' => '1.1.1',
                'expected.representations' => array_merge($error_representations, [
                    '\Mill\Examples\Showtimes\Representations\Movie' => [
                        'label' => 'Movie',
                        'description.length' => 41,
                        'content.keys' => [
                            'cast',
                            'content_rating',
                            'description',
                            'director',
                            'external_urls',
                            'external_urls.imdb',
                            'external_urls.tickets',
                            'external_urls.trailer',
                            'genres',
                            'id',
                            'kid_friendly',
                            'name',
                            'rotten_tomatoes_score',
                            'runtime',
                            'showtimes',
                            'theaters',
                            'uri'
                        ]
                    ],
                    '\Mill\Examples\Showtimes\Representations\Person' => [
                        'label' => 'Person',
                        'description.length' => 42,
                        'content.keys' => [
                            'id',
                            'imdb',
                            'name',
                            'uri'
                        ]
                    ],
                    '\Mill\Examples\Showtimes\Representations\Theater' => [
                        'label' => 'Theater',
                        'description.length' => 49,
                        'content.keys' => [
                            'address',
                            'id',
                            'movies',
                            'name',
                            'phone_number',
                            'showtimes',
                            'uri'
                        ]
                    ]
                ]),
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 32,
                                'actions.data' => [
                                    '/movie/+id::GET' => array_merge($common_actions['/movie/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/movies::GET' => array_merge($common_actions['/movies::GET'], [
                                        'params.keys' => [
                                            'location'
                                        ]
                                    ]),
                                    '/movies::POST' => array_merge($common_actions['/movies::POST'], [
                                        'params.keys' => [
                                            'cast',
                                            'content_rating',
                                            'description',
                                            'director',
                                            'genres',
                                            'imdb',
                                            'is_kid_friendly',
                                            'name',
                                            'rotten_tomatoes_score',
                                            'runtime',
                                            'trailer'
                                        ]
                                    ]),
                                    '/movies/+id::GET' => array_merge($common_actions['/movies/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/movies/+id::PATCH' => array_merge($common_actions['/movies/+id::PATCH'], [
                                        'params.keys' => [
                                            'cast',
                                            'content_rating',
                                            'description',
                                            'director',
                                            'genres',
                                            'imdb',
                                            'is_kid_friendly',
                                            'name',
                                            'rotten_tomatoes_score',
                                            'runtime',
                                            'trailer'
                                        ]
                                    ]),
                                    '/movies/+id::DELETE' => array_merge($common_actions['/movies/+id::DELETE'], [
                                        'params.keys' => []
                                    ])
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 40,
                                'actions.data' => [
                                    '/theaters::GET' => array_merge($common_actions['/theaters::GET'], [
                                        'params.keys' => [
                                            'location'
                                        ]
                                    ]),
                                    '/theaters::POST' => array_merge($common_actions['/theaters::POST'], [
                                        'params.keys' => [
                                            'address',
                                            'name',
                                            'phone_number'
                                        ]
                                    ]),
                                    '/theaters/+id::GET' => array_merge($common_actions['/theaters/+id::GET'], [
                                        'params.keys' => []
                                    ]),
                                    '/theaters/+id::PATCH' => array_merge($common_actions['/theaters/+id::PATCH'], [
                                        'params.keys' => [
                                            'address',
                                            'name',
                                            'phone_number'
                                        ]
                                    ]),
                                    '/theaters/+id::DELETE' => array_merge($common_actions['/theaters/+id::DELETE'], [
                                        'params.keys' => []
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
