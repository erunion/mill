<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\StringType;

class StringTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new StringType;

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
                  * @api-label Description
                  * @api-field description
                  * @api-type string
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'description',
                    'label' => 'Description',
                    'options' => false,
                    'type' => 'string',
                    'version' => false
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label Description
                  * @api-field description
                  * @api-type string
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'description',
                    'label' => 'Description',
                    'options' => false,
                    'type' => 'string',
                    'version' => false
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-label Description
                  * @api-field description
                  * @api-type string
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'description',
                    'label' => 'Description',
                    'options' => false,
                    'type' => 'string',
                    'version' => [
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label Description
                  * @api-field description
                  * @api-type string
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'description',
                    'label' => 'Description',
                    'options' => false,
                    'type' => 'string',
                    'version' => [
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ]
        ];
    }
}
