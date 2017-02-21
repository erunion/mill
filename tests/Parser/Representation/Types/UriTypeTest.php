<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\UriType;

class UriTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new UriType;

        $this->assertFalse($type->requiresSubtype());
        $this->assertFalse($type->requiresOptions());
    }

    /**
     * @return array
     */
    public function providerAnnotationWithType()
    {
        return [
            'bare' => [
                'docblock' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  * @api-type uri
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'uri',
                    'label' => 'Canonical relative URI',
                    'options' => false,
                    'type' => 'uri',
                    'version' => false
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  * @api-type uri
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'uri',
                    'label' => 'Canonical relative URI',
                    'options' => false,
                    'type' => 'uri',
                    'version' => false
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  * @api-type uri
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'uri',
                    'label' => 'Canonical relative URI',
                    'options' => false,
                    'type' => 'uri',
                    'version' => '1.2'
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label Canonical relative URI
                  * @api-field uri
                  * @api-type uri
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'uri',
                    'label' => 'Canonical relative URI',
                    'options' => false,
                    'type' => 'uri',
                    'version' => '1.2'
                ]
            ]
        ];
    }
}
