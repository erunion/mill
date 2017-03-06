<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\TypeAnnotation;
use Mill\Parser\Representation\Types\ArrayType;
use Mill\Parser\Representation\Types\BooleanType;
use Mill\Parser\Representation\Types\DatetimeType;
use Mill\Parser\Representation\Types\EnumType;
use Mill\Parser\Representation\Types\NumberType;
use Mill\Parser\Representation\Types\ObjectType;
use Mill\Parser\Representation\Types\RepresentationType;
use Mill\Parser\Representation\Types\StringType;
use Mill\Parser\Representation\Types\TimestampType;
use Mill\Parser\Representation\Types\UriType;
use Mill\Tests\TestCase;

class TypeAnnotationTest extends TestCase
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($type, $object, $subtype, $expected)
    {
        $annotation = new TypeAnnotation(
            $type,
            __CLASS__,
            __METHOD__,
            null,
            [
                'object' => $object,
                'subtype' => $subtype
            ]
        );

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertSame($type, $annotation->getType());
        $this->assertSame($object, $annotation->getObject());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['subtype'], $annotation->getSubtype());
        $this->assertFalse($annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            // `@api-type array`
            'array' => [
                'type' => 'array',
                'object' => new ArrayType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'array'
                ]
            ],

            // `@api-type boolean`
            'boolean' => [
                'type' => 'boolean',
                'object' => new BooleanType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'boolean'
                ]
            ],

            // `@api-type datetime`
            'datetime' => [
                'type' => 'datetime',
                'object' => new DatetimeType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'datetime'
                ]
            ],

            // `@api-type enum`
            'enum' => [
                'type' => 'enum',
                'object' => new EnumType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'enum'
                ]
            ],

            // `@api-type number`
            'number' => [
                'type' => 'number',
                'object' => new NumberType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'number'
                ]
            ],

            // `@api-type object`
            'object' => [
                'type' => 'object',
                'object' => new ObjectType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'object'
                ]
            ],

            // `@api-type response`
            'response' => [
                'type' => 'response',
                'object' => new RepresentationType,
                'subtype' => 'PosterRepresentation',
                'expected' => [
                    'subtype' => 'PosterRepresentation',
                    'type' => 'response'
                ]
            ],

            // `@api-type string`
            'string' => [
                'type' => 'string',
                'object' => new StringType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'string'
                ]
            ],

            // `@api-type timestamp`
            'timestamp' => [
                'type' => 'timestamp',
                'object' => new TimestampType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'timestamp'
                ]
            ],

            // `@api-type uri`
            'uri' => [
                'type' => 'uri',
                'object' => new UriType,
                'subtype' => false,
                'expected' => [
                    'subtype' => false,
                    'type' => 'uri'
                ]
            ]
        ];
    }
}
