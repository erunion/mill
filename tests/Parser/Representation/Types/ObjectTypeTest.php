<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\ObjectType;

class ObjectTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new ObjectType;

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
                  * @api-label External URLs
                  * @api-field urls
                  * @api-type object
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'urls',
                    'label' => 'External URLs',
                    'options' => false,
                    'type' => 'object',
                    'version' => false
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label External URLs
                  * @api-field urls
                  * @api-type object
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'urls',
                    'label' => 'External URLs',
                    'options' => false,
                    'type' => 'object',
                    'version' => false
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-label External URLs
                  * @api-field urls
                  * @api-type object
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'urls',
                    'label' => 'External URLs',
                    'options' => false,
                    'type' => 'object',
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
                  * @api-type object
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'urls',
                    'label' => 'External URLs',
                    'options' => false,
                    'type' => 'object',
                    'version' => [
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ]
        ];
    }
}
