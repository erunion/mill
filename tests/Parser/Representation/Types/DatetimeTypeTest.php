<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\DatetimeType;

class DatetimeTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new DatetimeType;

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
                  * @api-label Movie release date
                  * @api-field release_date
                  * @api-type datetime
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'release_date',
                    'label' => 'Movie release date',
                    'options' => false,
                    'type' => 'datetime',
                    'version' => false
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label Movie release date
                  * @api-field release_date
                  * @api-type datetime
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'release_date',
                    'label' => 'Movie release date',
                    'options' => false,
                    'type' => 'datetime',
                    'version' => false
                ]
            ],
            'version' => [
                'docblock' => '/**
                  * @api-label Movie release date
                  * @api-field release_date
                  * @api-type datetime
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'release_date',
                    'label' => 'Movie release date',
                    'options' => false,
                    'type' => 'datetime',
                    'version' => '1.2'
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label Movie release date
                  * @api-field release_date
                  * @api-type datetime
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'release_date',
                    'label' => 'Movie release date',
                    'options' => false,
                    'type' => 'datetime',
                    'version' => '1.2'
                ]
            ]
        ];
    }
}
