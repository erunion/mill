<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Tests\TestCase;

class FieldAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($docblock, $expected)
    {
        $annotation = $this->getFieldAnnotationFromDocblock($docblock, __CLASS__, __METHOD__);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($expected['field'], $annotation->getFieldName());
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
                  * @api-label MPAA rating
                  * @api-field content_rating
                  * @api-type string
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'content_rating',
                    'label' => 'MPAA rating',
                    'options' => false,
                    'type' => 'string',
                    'version' => false
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-label MPAA rating
                  * @api-field content_rating
                  * @api-type string
                  * @api-version 1.0
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'content_rating',
                    'label' => 'MPAA rating',
                    'options' => false,
                    'type' => 'string',
                    'version' => '1.0'
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label URL to purchase tickets
                  * @api-field tickets.url
                  * @api-type string
                  * @api-capability BUY_TICKETS
                  */',
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'field' => 'tickets.url',
                    'label' => 'URL to purchase tickets',
                    'options' => false,
                    'type' => 'string',
                    'version' => false
                ]
            ],
            'options' => [
                'docblock' => '/**
                  * @api-label MPAA rating
                  * @api-field content_rating
                  * @api-type enum
                  * @api-options [G|PG|PG-13|R|NC-17|X|NR|UR]
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'content_rating',
                    'label' => 'MPAA rating',
                    'options' => [
                        'G',
                        'NC-17',
                        'NR',
                        'PG',
                        'PG-13',
                        'R',
                        'UR',
                        'X'
                    ],
                    'type' => 'enum',
                    'version' => false
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label MPAA rating
                  * @api-field content_rating
                  * @api-type enum
                  * @api-version 1.0
                  * @api-capability MOVIE_RATINGS
                  * @api-options [G|PG|PG-13|R|NC-17|X|NR|UR]
                  *',
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'field' => 'content_rating',
                    'label' => 'MPAA rating',
                    'options' => [
                        'G',
                        'NC-17',
                        'NR',
                        'PG',
                        'PG-13',
                        'R',
                        'UR',
                        'X'
                    ],
                    'type' => 'enum',
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
                'annotation' => '\Mill\Parser\Annotations\FieldAnnotation',
                'docblock' => '/**
                    * @api-label MPAA rating
                    * @api-field content_rating
                    * @api-type zuul
                    */',
                'expected.exception' => '\Mill\Exceptions\Representation\Types\InvalidTypeException',
                'expected.exception.asserts' => [
                    'getType' => 'zuul'
                ]
            ],
            'restricted-field-name-is-detected' => [
                'annotation' => '\Mill\Parser\Annotations\FieldAnnotation',
                'docblock' => '/**
                    * @api-label This is an restricted field name
                    * @api-field __FIELD_DATA__
                    * @api-type string
                    */',
                'expected.exception' => '\Mill\Exceptions\Representation\RestrictedFieldNameException',
                'expected.exception.asserts' => []
            ]
        ];
    }
}
