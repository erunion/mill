<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\EnumType;

class EnumTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new EnumType;

        $this->assertFalse($type->requiresSubtype());
        $this->assertTrue($type->requiresOptions());
    }

    /**
     * @expectedException \Mill\Exceptions\Representation\Types\MissingOptionsException
     */
    public function testAnnotationWithTypeFailsIfNoOptionsArePresent()
    {
        $docblock = <<<DOCBLOCK
/**
 * @api-label MPAA rating
 * @api-field content_rating
 * @api-type enum
 */
DOCBLOCK;

        $this->getFieldAnnotationFromDocblock($docblock);
    }

    /**
     * @return array
     */
    public function annotationTypeProvider()
    {
        return [
            'bare' => [
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
            'capability' => [
                'docblock' => '/**
                  * @api-label MPAA rating
                  * @api-field content_rating
                  * @api-type enum
                  * @api-options [G|PG|PG-13|R|NC-17|X|NR|UR]
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
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
            'versioned' => [
                'docblock' => '/**
                  * @api-label MPAA rating
                  * @api-field content_rating
                  * @api-type enum
                  * @api-options [G|PG|PG-13|R|NC-17|X|NR|UR]
                  * @api-version 1.2
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
                    'version' => [
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label MPAA rating
                  * @api-field content_rating
                  * @api-type enum
                  * @api-options [G|PG|PG-13|R|NC-17|X|NR|UR]
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
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
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ]
        ];
    }
}
