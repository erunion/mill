<?php
namespace Mill\Tests\Parser\Annotations;

class DataAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, array $expected)
    {
        $annotation = $this->getDataAnnotationFromDocblock($content, __CLASS__);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($expected['identifier'], $annotation->getIdentifier());
        $this->assertSame($expected, $annotation->toArray());

        if (is_string($expected['capability'])) {
            $this->assertInstanceOf(
                '\Mill\Parser\Annotations\CapabilityAnnotation',
                $annotation->getCapability()
            );
        } else {
            $this->assertFalse($annotation->getCapability());
        }

        if ($expected['version']) {
            $this->assertInstanceOf('\Mill\Parser\Version', $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            'bare' => [
                'content' => '/**
                  * @api-data content_rating (string) - MPAA rating
                  */',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'nullable' => false,
                    'sample_data' => false,
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => false
                ]
            ],
            'versioned' => [
                'content' => '/**
                  * @api-data content_rating (string) - MPAA rating
                  * @api-version 1.0
                  */',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'nullable' => false,
                    'sample_data' => false,
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => '1.0'
                ]
            ],
            'nullable' => [
                'content' => '/**
                  * @api-data tickets.url (string, nullable) - URL to purchase tickets
                  */',
                'expected' => [
                    'capability' => false,
                    'description' => 'URL to purchase tickets',
                    'identifier' => 'tickets.url',
                    'nullable' => true,
                    'sample_data' => false,
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => false
                ]
            ],
            'capability' => [
                'content' => '/**
                  * @api-data tickets.url (string, BUY_TICKETS) - URL to purchase tickets
                  */',
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'URL to purchase tickets',
                    'identifier' => 'tickets.url',
                    'nullable' => false,
                    'sample_data' => false,
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => false
                ]
            ],
            'options' => [
                'content' => '/**
                  * @api-data content_rating (enum) - MPAA rating
                  *  + Members
                  *    - `G`
                  *    - `PG`
                  *    - `PG-13`
                  *    - `R`
                  *    - `NC-17`
                  *    - `X`
                  *    - `NR`
                  *    - `UR`
                  */',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'nullable' => false,
                    'sample_data' => 'G',
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'G' => '',
                        'NC-17' => '',
                        'NR' => '',
                        'PG' => '',
                        'PG-13' => '',
                        'R' => '',
                        'UR' => '',
                        'X' => ''
                    ],
                    'version' => false
                ]
            ],
            'scoped' => [
                'content' => '/**
                  * @api-data tickets.url (string) - URL to purchase tickets
                  * @api-scope public
                  */',
                'expected' => [
                    'capability' => false,
                    'description' => 'URL to purchase tickets',
                    'identifier' => 'tickets.url',
                    'nullable' => false,
                    'sample_data' => false,
                    'scopes' => [
                        [
                            'description' => false,
                            'scope' => 'public'
                        ]
                    ],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => false
                ]
            ],
            '_complete' => [
                'content' => '/**
                  * @api-data content_rating (enum, nullable, MOVIE_RATINGS) - MPAA rating
                  *  + Members
                  *    - `G`
                  *    - `PG`
                  *    - `PG-13`
                  *    - `R`
                  *    - `NC-17`
                  *    - `X`
                  *    - `NR`
                  *    - `UR`
                  * @api-version 1.0
                  * @api-scope public
                  *',
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'nullable' => true,
                    'sample_data' => 'G',
                    'scopes' => [
                        [
                            'description' => false,
                            'scope' => 'public'
                        ]
                    ],
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'G' => '',
                        'NC-17' => '',
                        'NR' => '',
                        'PG' => '',
                        'PG-13' => '',
                        'R' => '',
                        'UR' => '',
                        'X' => ''
                    ],
                    'version' => '1.0'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidContent()
    {
        return [
            'invalid-type-is-detected' => [
                'annotation' => '\Mill\Parser\Annotations\DataAnnotation',
                'content' => '/**
                    * @api-data content_rating (zuul) - MPAA rating
                    */',
                'expected.exception' => '\Mill\Exceptions\Annotations\UnsupportedTypeException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'content_rating (zuul) - MPAA rating'
                ]
            ],
            'restricted-field-name-is-detected' => [
                'annotation' => '\Mill\Parser\Annotations\DataAnnotation',
                'content' => '/**
                    * @api-data __FIELD_DATA__ (string) - This is an restricted field name
                    */',
                'expected.exception' => '\Mill\Exceptions\Representation\RestrictedFieldNameException',
                'expected.exception.asserts' => []
            ]
        ];
    }
}
