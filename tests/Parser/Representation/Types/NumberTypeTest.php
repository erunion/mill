<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\NumberType;

class NumberTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new NumberType;

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
                  * @api-label Movie runtime in seconds
                  * @api-field runtime
                  * @api-type number
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'runtime',
                    'label' => 'Movie runtime in seconds',
                    'options' => false,
                    'type' => 'number',
                    'version' => false
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label Movie runtime in seconds
                  * @api-field runtime
                  * @api-type number
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'runtime',
                    'label' => 'Movie runtime in seconds',
                    'options' => false,
                    'type' => 'number',
                    'version' => false
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-label Movie runtime in seconds
                  * @api-field runtime
                  * @api-type number
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'runtime',
                    'label' => 'Movie runtime in seconds',
                    'options' => false,
                    'type' => 'number',
                    'version' => '1.2'
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label Movie runtime in seconds
                  * @api-field runtime
                  * @api-type number
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'runtime',
                    'label' => 'Movie runtime in seconds',
                    'options' => false,
                    'type' => 'number',
                    'version' => '1.2'
                ]
            ]
        ];
    }
}
