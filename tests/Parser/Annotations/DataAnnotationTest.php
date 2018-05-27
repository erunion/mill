<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Annotations\VendorTagAnnotation;
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
        $this->assertFalse($annotation->supportsAliasing());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertTrue($annotation->supportsVendorTags());
        $this->assertFalse($annotation->requiresVisibilityDecorator());

        $this->assertSame($expected['identifier'], $annotation->getIdentifier());
        $this->assertSame($expected, $annotation->toArray());

        $this->assertSame(
            $expected['vendor_tags'],
            array_map(
                function (VendorTagAnnotation $tag): string {
                    return $tag->getVendorTag();
                },
                $annotation->getVendorTags()
            )
        );

        if ($expected['version']) {
            $this->assertInstanceOf(Version::class, $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }
    }

    public function providerAnnotation(): array
    {
        return [
            '_complete' => [
                'content' => '/**
                  * @api-data content_rating `G` (enum, nullable, tag:MOVIE_RATINGS) - MPAA rating
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
                    'vendor_tags' => [
                        'tag:MOVIE_RATINGS'
                    ],
                    'version' => '1.0'
                ]
            ],
            'bare' => [
                'content' => '/**
                  * @api-data content_rating (string) - MPAA rating
                  */',
                'version' => null,
                'expected' => [
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'nullable' => false,
                    'sample_data' => false,
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
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
                    'description' => 'MPAA rating',
                    'identifier' => 'content_rating',
                    'nullable' => false,
                    'sample_data' => false,
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => '1.0'
                ]
            ],
            'nullable' => [
                'content' => '/**
                  * @api-data tickets.url (string, nullable) - URL to purchase tickets
                  */',
                'version' => null,
                'expected' => [
                    'description' => 'URL to purchase tickets',
                    'identifier' => 'tickets.url',
                    'nullable' => true,
                    'sample_data' => false,
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
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
                    'vendor_tags' => [],
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
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => false
                ]
            ],
            'vendor-tag' => [
                'content' => '/**
                  * @api-data tickets.url (string, tag:BUY_TICKETS) - URL to purchase tickets
                  */',
                'version' => null,
                'expected' => [
                    'description' => 'URL to purchase tickets',
                    'identifier' => 'tickets.url',
                    'nullable' => false,
                    'sample_data' => false,
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [
                        'tag:BUY_TICKETS'
                    ],
                    'version' => false
                ]
            ],
            'zeroed-out-sample_data' => [
                'content' => '/**
                  * @api-data is_staff `0` (boolean) - Is this user a staff member?
                  */',
                'version' => null,
                'expected' => [
                    'description' => 'Is this user a staff member?',
                    'identifier' => 'is_staff',
                    'nullable' => false,
                    'sample_data' => '0',
                    'scopes' => [],
                    'subtype' => false,
                    'type' => 'boolean',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => false
                ]
            ],
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
                    * @api-data __NESTED_DATA__ (string) - This is an restricted field name
                    */',
                'expected.exception' => RestrictedFieldNameException::class,
                'expected.exception.asserts' => []
            ]
        ];
    }
}
