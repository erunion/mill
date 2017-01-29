<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Parser\Representation\Types\RepresentationType;

class RepresentationTypeTest extends TypeTest
{
    public function testType()
    {
        $type = new RepresentationType;

        $this->assertTrue($type->requiresSubtype());
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
                  * @api-label Theater
                  * @api-field theater
                  * @api-type representation
                  * @api-subtype \Mill\Examples\Showtimes\Representations\Theater
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'theater',
                    'label' => 'Theater',
                    'options' => false,
                    'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                    'type' => 'representation',
                    'version' => false,
                ]
            ],
            'capability' => [
                'docblock' => '/**
                  * @api-label Theater
                  * @api-field theater
                  * @api-type representation
                  * @api-subtype \Mill\Examples\Showtimes\Representations\Theater
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'theater',
                    'label' => 'Theater',
                    'options' => false,
                    'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                    'type' => 'representation',
                    'version' => false,
                ]
            ],
            'versioned' => [
                'docblock' => '/**
                  * @api-label Theater
                  * @api-field theater
                  * @api-type representation
                  * @api-subtype \Mill\Examples\Showtimes\Representations\Theater
                  * @api-version 1.2
                  */',
                'expected' => [
                    'capability' => false,
                    'field' => 'theater',
                    'label' => 'Theater',
                    'options' => false,
                    'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                    'type' => 'representation',
                    'version' => [
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ],
            '_complete' => [
                'docblock' => '/**
                  * @api-label Theater
                  * @api-field theater
                  * @api-type representation
                  * @api-subtype \Mill\Examples\Showtimes\Representations\Theater
                  * @api-version 1.2
                  * @api-capability NONE
                  */',
                'expected' => [
                    'capability' => 'NONE',
                    'field' => 'theater',
                    'label' => 'Theater',
                    'options' => false,
                    'subtype' => '\Mill\Examples\Showtimes\Representations\Theater',
                    'type' => 'representation',
                    'version' => [
                        'start' => '1.2',
                        'end' => '1.2'
                    ]
                ]
            ],
        ];
    }
}
