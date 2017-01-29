<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Tests\TestCase;

class FieldAnnotationTest extends TestCase
{
    /**
     * @dataProvider annotationProvider
     */
    public function testAnnotation($docblock, $expected)
    {
        $annotation = $this->getFieldAnnotationFromDocblock($docblock);

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

        if (is_array($expected['version'])) {
            $this->assertInstanceOf('\Mill\Parser\Version', $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }
    }

    /**
     * @dataProvider badAnnotationProvider
     */
    public function testAnnotationFailsOnInvalidAnnotations($docblock, $exception, $regex = [])
    {
        $this->expectException($exception);
        foreach ($regex as $rule) {
            $this->expectExceptionMessageRegExp($rule);
        }

        $this->getFieldAnnotationFromDocblock($docblock);
    }

    /**
     * @return array
     */
    public function annotationProvider()
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
                    'version' => [
                        'start' => '1.0',
                        'end' => '1.0'
                    ]
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
                        'PG',
                        'PG-13',
                        'R',
                        'NC-17',
                        'X',
                        'NR',
                        'UR'
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
                        'PG',
                        'PG-13',
                        'R',
                        'NC-17',
                        'X',
                        'NR',
                        'UR'
                    ],
                    'type' => 'enum',
                    'version' => [
                        'start' => '1.0',
                        'end' => '1.0'
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function badAnnotationProvider()
    {
        return [
            'invalid-type-is-detected' => [
                'docblock' => '/**
                    * @api-label MPAA rating
                    * @api-field content_rating
                    * @api-type zuul
                    */',
                'expected.exception' => '\Mill\Exceptions\Representation\Types\InvalidTypeException',
                'expected.exception.regex' => [
                    '/zuul/'
                ]
            ],
            'restricted-field-name-is-detected' => [
                'docblock' => '/**
                    * @api-label This is an restricted field name
                    * @api-field __FIELD_DATA__
                    * @api-type string
                    */',
                'expected.exception' => '\Mill\Exceptions\Representation\RestrictedFieldNameException',
                'expected.exception.regex' => [
                    '/`__FIELD_DATA__`/'
                ]
            ]
        ];
    }
}
