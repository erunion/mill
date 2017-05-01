<?php
namespace Mill\Tests\Parser\Annotations;

class DataAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $docblock
     * @param string $expected
     * @return void
     */
    public function testAnnotation($docblock, $expected)
    {
        $annotation = $this->getDataAnnotationFromDocblock($docblock, __CLASS__);

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
                'docblock' => '/**
                  * @api-data content_rating (string) - MPAA rating
                  */',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'sample_data' => false,
                    'subtype' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => false
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-data content_rating (string) - MPAA rating
                  * @api-version 1.0
                  */',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'sample_data' => false,
                    'subtype' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => '1.0'
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-data tickets.url (string, BUY_TICKETS) - URL to purchase tickets
                  */',
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'URL to purchase tickets',
                    'identifier' => 'tickets.url',
                    'sample_data' => false,
                    'subtype' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => false
                ]
            ],
            'options' => [
                'docblock' => '/**
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
                    'sample_data' => false,
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
            '_complete' => [
                'docblock' => '/**
                  * @api-data content_rating (enum, MOVIE_RATINGS) - MPAA rating
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
                  *',
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'sample_data' => false,
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
    public function providerAnnotationFailsOnInvalidAnnotations()
    {
        return [
            'invalid-type-is-detected' => [
                'annotation' => '\Mill\Parser\Annotations\DataAnnotation',
                'docblock' => '/**
                    * @api-data content_rating (zuul) - MPAA rating
                    */',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\UnsupportedTypeException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'content_rating (zuul) - MPAA rating'
                ]
            ],
            'restricted-field-name-is-detected' => [
                'annotation' => '\Mill\Parser\Annotations\DataAnnotation',
                'docblock' => '/**
                    * @api-data __FIELD_DATA__ (string) - This is an restricted field name
                    */',
                'expected.exception' => '\Mill\Exceptions\Representation\RestrictedFieldNameException',
                'expected.exception.asserts' => []
            ]
        ];
    }
}