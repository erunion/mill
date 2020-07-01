<?php
namespace Mill\Tests\Compiler;

use Mill\Compiler\ErrorMap;
use Mill\Tests\TestCase;

class ErrorMapTest extends TestCase
{
    /**
     * @dataProvider providerTestCompilation
     * @param bool $private_objects
     * @param array|null $vendor_tags
     * @param array $expected
     */
    public function testCompilation(bool $private_objects, ?array $vendor_tags, array $expected): void
    {
        $compiler = new ErrorMap($this->getApplication());
        $compiler->setLoadPrivateDocs($private_objects);
        $compiler->setLoadVendorTagDocs($vendor_tags);

        $error_map = $compiler->getCompiled();
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
    public function providerTestCompilation(): array
    {
        // Save us the effort of copy and pasting the same action data over and over.
        $errors = [
            '/movies/+id::PATCH:666' => [
                'path' => '/movies/{id}',
                'method' => 'PATCH',
                'http_code' => '403 Forbidden',
                'error_code' => '666',
                'description' => 'If the user is not allowed to edit that movie.'
            ],
            '/movies/+id::PATCH:1337' => [
                'path' => '/movies/{id}',
                'method' => 'PATCH',
                'http_code' => '403 Forbidden',
                'error_code' => '1337',
                'description' => 'If something cool happened.'
            ],
            '/theaters/+id::PATCH::1337' => [
                'path' => '/theaters/{id}',
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
                'vendor_tags' => null,
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

            // Error map with public-only parsed docs and all vendor tags.
            'error-map-public-docs-with-all-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:BUY_TICKETS',
                    'tag:DELETE_CONTENT',
                    'tag:FEATURE_FLAG',
                    'tag:MOVIE_RATINGS'
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

            // Error map with public-only parsed docs and unmatched vendor tags.
            'error-map-public-docs-with-unmatched-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:BUY_TICKETS',
                    'tag:FEATURE_FLAG'
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

            // Error map with public-only parsed docs and matched vendor tags.
            'error-map-public-docs-with-matched-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:DELETE_CONTENT'
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

            // Error map with public-only parsed docs.
            'error-map-public-docs' => [
                'private_objects' => false,
                'vendor_tags' => [],
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
