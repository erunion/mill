<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\ArrayType;

class ArrayTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new ArrayType;

        $this->assertFalse($type->requiresSubtype());
        $this->assertFalse($type->requiresOptions());
    }

    /**
     * @return array
     */
    public function annotationTypeProvider()
    {
        return [
            'bare' => [
                'docblock' => '/**
                  * @api-label Movie genres
                  * @api-field genres
                  * @api-type array
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'genres',
                    'label' => 'Movie genres',
                    'options' => false,
                    'subtype' => false,
                    'type' => 'array',
                    'version' => false
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label Movie genres
                  * @api-field genres
                  * @api-type array
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'genres',
                    'label' => 'Movie genres',
                    'options' => false,
                    'subtype' => false,
                    'type' => 'array',
                    'version' => false
                ]
            ],
            'subtype' => [
                'docblock' => '/**
                  * @api-label External URLs
                  * @api-field urls
                  * @api-type array
                  * @api-subtype object
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'urls',
                    'label' => 'External URLs',
                    'options' => false,
                    'subtype' => 'object',
                    'type' => 'array',
                    'version' => false
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-label External URLs
                  * @api-field urls
                  * @api-type array
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'urls',
                    'label' => 'External URLs',
                    'options' => false,
                    'subtype' => false,
                    'type' => 'array',
                    'version' => [
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label External URLs
                  * @api-field urls
                  * @api-type array
                  * @api-subtype object
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'urls',
                    'label' => 'External URLs',
                    'options' => false,
                    'subtype' => 'object',
                    'type' => 'array',
                    'version' => [
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ]
        ];
    }
}
