<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Exceptions\Representation\Types\MissingOptionsException;
use Mill\Parser\Representation\Types\EnumType;

class EnumTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new EnumType;

        $this->assertFalse($type->requiresSubtype());
        $this->assertTrue($type->requiresOptions());
    }

    public function testAnnotationWithTypeFailsIfNoOptionsArePresent()
    {
        $docblock = <<<DOCBLOCK
/**
 * @api-label MPAA rating
 * @api-field content_rating
 * @api-type enum
 */
DOCBLOCK;

        try {
            $this->getFieldAnnotationFromDocblock($docblock, __CLASS__, __METHOD__);
        } catch (MissingOptionsException $e) {
            $this->assertSame('enum', $e->getType());
            $this->assertNull($e->getField());
            $this->assertNull($e->getAnnotation());
            $this->assertSame(__CLASS__, $e->getClass());
            $this->assertSame(__METHOD__, $e->getMethod());
        }
    }

    /**
     * @return array
     */
    public function providerAnnotationWithType()
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
                    'version' => '1.2'
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
                    'version' => '1.2'
                ]
            ]
        ];
    }
}
