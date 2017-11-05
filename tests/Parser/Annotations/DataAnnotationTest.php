<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Parser\Annotations\CapabilityAnnotation;
use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Version;

class DataAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param array $expected
     */
    public function testAnnotation(string $content, ?Version $version, array $expected): void
    {
        $annotation = $this->getDataAnnotationFromDocblock($content, __CLASS__);
        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param array $expected
     */
    public function testHydrate(string $content, ?Version $version, array $expected): void
    {
        $annotation = DataAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ), $version);

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(DataAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($expected['identifier'], $annotation->getIdentifier());
        $this->assertSame($expected, $annotation->toArray());

        if (is_string($expected['capability'])) {
            $this->assertInstanceOf(CapabilityAnnotation::class, $annotation->getCapability());
        } else {
            $this->assertFalse($annotation->getCapability());
        }

        if ($expected['version']) {
            $this->assertInstanceOf(Version::class, $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }
    }

    public function providerAnnotation(): array
    {
        return [
            'bare' => [
                'content' => '/**
                  * @api-data content_rating (string) - MPAA rating
                  */',
                'version' => null,
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
                'version' => new Version('1.0', __CLASS__, __METHOD__),
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
                'version' => null,
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
                'version' => null,
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
                'version' => null,
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
                'version' => null,
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
                'version' => new Version('1.0', __CLASS__, __METHOD__),
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

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'invalid-type-is-detected' => [
                'annotation' => DataAnnotation::class,
                'content' => '/**
                    * @api-data content_rating (zuul) - MPAA rating
                    */',
                'expected.exception' => UnsupportedTypeException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'content_rating (zuul) - MPAA rating'
                ]
            ],
            'restricted-field-name-is-detected' => [
                'annotation' => DataAnnotation::class,
                'content' => '/**
                    * @api-data __FIELD_DATA__ (string) - This is an restricted field name
                    */',
                'expected.exception' => RestrictedFieldNameException::class,
                'expected.exception.asserts' => []
            ]
        ];
    }
}
