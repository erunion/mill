<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\BooleanType;

class BooleanTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new BooleanType;

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
                  * @api-label Does the movie have a trailer?
                  * @api-field connections.trailer.exists
                  * @api-type boolean
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'connections.trailer.exists',
                    'label' => 'Does the movie have a trailer?',
                    'options' => false,
                    'type' => 'boolean',
                    'version' => false
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label Does the movie have a trailer?
                  * @api-field connections.trailer.exists
                  * @api-type boolean
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'connections.trailer.exists',
                    'label' => 'Does the movie have a trailer?',
                    'options' => false,
                    'type' => 'boolean',
                    'version' => false
                ]
            ],
            'version' => [
                'docblock' => '/*
                  * @api-label Does the movie have a trailer?
                  * @api-field connections.trailer.exists
                  * @api-type boolean
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'connections.trailer.exists',
                    'label' => 'Does the movie have a trailer?',
                    'options' => false,
                    'type' => 'boolean',
                    'version' => '1.2'
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label Does the movie have a trailer?
                  * @api-field connections.trailer.exists
                  * @api-type boolean
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'connections.trailer.exists',
                    'label' => 'Does the movie have a trailer?',
                    'options' => false,
                    'type' => 'boolean',
                    'version' => '1.2'
                ]
            ]
        ];
    }
}
