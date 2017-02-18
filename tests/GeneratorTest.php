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

        $this->assertCount(2, $generator->getResources());
    }

    /**
     * @dataProvider providerGeneratorWithVersion
     */
    public function testGeneratorWithVersion($version, $expected_representations, $expected_resources)
    {
        $generator = new Generator($this->getConfig(), new Version($version, __CLASS__, __METHOD__));
        $generator->generate();

        /**
         * Verify resources
         */
        $resources = $generator->getResources();
        $this->assertArrayHasKey($version, $resources);

        $resources = $resources[$version];
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
                        $expected_action['params.size'],
                        count($action->getParameters()),
                        $i . ' does not have the right amount of parameters.'
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

        foreach ($expected_representations as $name => $data) {
            $this->assertArrayHasKey($name, $representations);

            /** @var \Mill\Parser\Representation\Documentation $representation */
            $representation = $representations[$name];
            $actual = $representation->toArray();

            $this->assertSame($data['label'], $actual['label']);
            $this->assertSame($data['description.length'], strlen($actual['description']));
            $this->assertCount($data['content.size'], $actual['content']);
            $this->assertCount($data['content.size'], $representation->getContent());
        }
    }

    /**
     * @return array
     */
    public function providerGeneratorWithVersion()
    {
        return [
            'version-1.0' => [
                'version' => '1.0',
                'expected.representations' => [
                    '\Mill\Examples\Showtimes\Representations\Movie' => [
                        'label' => 'Movie',
                        'description.length' => 41,
                        'content.size' => 14
                    ],
                    '\Mill\Examples\Showtimes\Representations\Theater' => [
                        'label' => 'Theater',
                        'description.length' => 49,
                        'content.size' => 7
                    ]
                ],
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 0,
                                'actions.data' => [
                                    '/movies::GET' => [
                                        'uri' => '/movies',
                                        'method' => 'GET',
                                        'uriSegment' => false,
                                        'params.size' => 1
                                    ],
                                    '/movies::POST' => [
                                        'uri' => '/movies',
                                        'method' => 'POST',
                                        'uriSegment' => false,
                                        'params.size' => 7
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
                                        'params.size' => 0
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
                                        'params.size' => 0
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 0,
                                'actions.data' => [
                                    '/theaters::GET' => [
                                        'uri' => '/theaters',
                                        'method' => 'GET',
                                        'uriSegment' => false,
                                        'params.size' => 1
                                    ],
                                    '/theaters::POST' => [
                                        'uri' => '/theaters',
                                        'method' => 'POST',
                                        'uriSegment' => false,
                                        'params.size' => 3
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
                                        'params.size' => 0
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
                                        'params.size' => 3
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
                                        'params.size' => 0
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            'version-1.1' => [
                'version' => '1.1',
                'expected.representations' => [
                    '\Mill\Examples\Showtimes\Representations\Movie' => [
                        'label' => 'Movie',
                        'description.length' => 41,
                        'content.size' => 14
                    ],
                    '\Mill\Examples\Showtimes\Representations\Theater' => [
                        'label' => 'Theater',
                        'description.length' => 49,
                        'content.size' => 6
                    ]
                ],
                'expected.resources' => [
                    'Movies' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movies',
                                'description.length' => 0,
                                'actions.data' => [
                                    '/movies::GET' => [
                                        'uri' => '/movies',
                                        'method' => 'GET',
                                        'uriSegment' => false,
                                        'params.size' => 1
                                    ],
                                    '/movies::POST' => [
                                        'uri' => '/movies',
                                        'method' => 'POST',
                                        'uriSegment' => false,
                                        'params.size' => 9
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
                                        'params.size' => 0
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
                                        'params.size' => 9
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
                                        'params.size' => 0
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'Theaters' => [
                        'resources' => [
                            [
                                'resource.name' => 'Movie Theaters',
                                'description.length' => 0,
                                'actions.data' => [
                                    '/theaters::GET' => [
                                        'uri' => '/theaters',
                                        'method' => 'GET',
                                        'uriSegment' => false,
                                        'params.size' => 1
                                    ],
                                    '/theaters::POST' => [
                                        'uri' => '/theaters',
                                        'method' => 'POST',
                                        'uriSegment' => false,
                                        'params.size' => 3
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
                                        'params.size' => 0
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
                                        'params.size' => 3
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
                                        'params.size' => 0
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
