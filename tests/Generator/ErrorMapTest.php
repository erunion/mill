<?php
namespace Mill\Tests\Generator;

use Mill\Generator\ErrorMap;
use Mill\Tests\TestCase;

class ErrorMapTestTest extends TestCase
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
        $generator = new ErrorMap($this->getConfig());
        $generator->setLoadPrivateDocs($private_objects);
        $generator->setLoadCapabilityDocs($capabilities);
        $error_map = $generator->generate();

        $this->assertSame(array_keys($expected), array_keys($error_map));

        foreach ($expected as $version => $expected_actions) {
            $this->assertSame(
                array_keys($expected_actions),
                array_keys($error_map[$version]),
                'Error map for v' . $version . ' does not have the same array keys.'
            );

            foreach ($expected_actions as $action => $errors) {
                $this->assertSame(
                    $errors,
                    $error_map[$version][$action],
                    'The `' . $action . '` changes for v' . $version . ' don\'t match up.'
                );
            }
        }
    }

    /**
     * @return array
     */
    public function providerTestGeneration()
    {
        // Save us the effort of copy and pasting the same action data over and over.
        $errors = [
            '/movies/+id::PATCH:666' => [
                'uri' => '/movies/{id}',
                'method' => 'PATCH',
                'http_code' => '403 Forbidden',
                'error_code' => '666',
                'description' => 'If the user is not allowed to edit that movie.'
            ],
            '/movies/+id::PATCH:1337' => [
                'uri' => '/movies/{id}',
                'method' => 'PATCH',
                'http_code' => '403 Forbidden',
                'error_code' => '1337',
                'description' => 'If something cool happened.'
            ],
            '/theaters/+id::PATCH::1337' => [
                'uri' => '/theaters/{id}',
                'method' => 'PATCH',
                'http_code' => '403 Forbidden',
                'error_code' => '1337',
                'description' => 'If something cool happened.'
            ]
        ];

        return [
            // Complete error map. All documentation parsed.
            'complete-error-map' => [
                'private_objects' => true,
                'capabilities' => null,
                'expected' => [
                    '1.0' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.3' => [
                        'Movies' => [
                            '666' => [
                                $errors['/movies/+id::PATCH:666'],
                            ],
                            '1337' => [
                                $errors['/movies/+id::PATCH:1337']
                            ]
                        ]
                    ]
                ]
            ],

            // Error map with public-only parsed docs and all capabilities.
            'error-map-public-docs-with-all-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'BUY_TICKETS',
                    'DELETE_CONTENT',
                    'FEATURE_FLAG',
                    'MOVIE_RATINGS'
                ],
                'expected' => [
                    '1.0' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.3' => [
                        'Movies' => [
                            '666' => [
                                $errors['/movies/+id::PATCH:666']
                            ]
                        ]
                    ]
                ]
            ],

            // Error map with public-only parsed docs and unmatched capabilities
            'error-map-public-docs-with-unmatched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'BUY_TICKETS',
                    'FEATURE_FLAG'
                ],
                'expected' => [
                    '1.0' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.3' => [
                        'Movies' => [
                            '666' => [
                                $errors['/movies/+id::PATCH:666']
                            ]
                        ]
                    ]
                ],
            ],

            // Error map with public-only parsed docs and matched capabilities
            'error-map-public-docs-with-matched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'DELETE_CONTENT'
                ],
                'expected' => [
                    '1.0' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.3' => [
                        'Movies' => [
                            '666' => [
                                $errors['/movies/+id::PATCH:666']
                            ]
                        ]
                    ]
                ]
            ],

            // Error map with public-only parsed docs
            'error-map-public-docs' => [
                'private_objects' => false,
                'capabilities' => [],
                'expected' => [
                    '1.0' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.1' => [
                        'Theaters' => [
                            '1337' => [
                                $errors['/theaters/+id::PATCH::1337']
                            ]
                        ]
                    ],
                    '1.1.3' => [
                        'Movies' => [
                            '666' => [
                                $errors['/movies/+id::PATCH:666']
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
