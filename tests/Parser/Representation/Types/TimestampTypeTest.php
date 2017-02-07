<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\TimestampType;

class TimestampTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new TimestampType;

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
                  * @api-label The time the object was created
                  * @api-field created_time
                  * @api-type timestamp
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'created_time',
                    'label' => 'The time the object was created',
                    'options' => false,
                    'type' => 'timestamp',
                    'version' => false,
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label The time the object was created
                  * @api-field created_time
                  * @api-type timestamp
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'created_time',
                    'label' => 'The time the object was created',
                    'options' => false,
                    'type' => 'timestamp',
                    'version' => false,
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-label The time the object was created
                  * @api-field created_time
                  * @api-type timestamp
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'created_time',
                    'label' => 'The time the object was created',
                    'options' => false,
                    'type' => 'timestamp',
                    'version' => '1.2'
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label The time the object was created
                  * @api-field created_time
                  * @api-type timestamp
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'created_time',
                    'label' => 'The time the object was created',
                    'options' => false,
                    'type' => 'timestamp',
                    'version' => '1.2'
                ]
            ]
        ];
    }
}
