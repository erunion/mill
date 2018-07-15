<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\InvalidMSONSyntaxException;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Parser\Annotations\PathParamAnnotation;

class PathParamAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $param
     * @param array $expected
     */
    public function testAnnotation(string $param, array $expected): void
    {
        $annotation = new PathParamAnnotation($param, __CLASS__, __METHOD__, null);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(PathParamAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsVendorTags());
        $this->assertFalse($annotation->requiresVisibilityDecorator());

        $this->assertSame('path', $annotation->getPayloadFormat());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['field'], $annotation->getField());
        $this->assertSame($expected['type'], $annotation->getType());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertEmpty($annotation->getVendorTags());
        $this->assertFalse($annotation->getVersion());
    }

    public function providerAnnotation(): array
    {
        return [
            'bare' => [
                'param' => 'id (string) - Movie ID',
                'expected' => [
                    'description' => 'Movie ID',
                    'field' => 'id',
                    'required' => true,
                    'sample_data' => false,
                    'type' => 'string',
                    'values' => []
                ]
            ],
            'sample_data' => [
                'param' => 'id `1234` (string) - Movie ID',
                'expected' => [
                    'description' => 'Movie ID',
                    'field' => 'id',
                    'required' => true,
                    'sample_data' => '1234',
                    'type' => 'string',
                    'values' => []
                ]
            ],
            '_complete' => [
                'param' => 'date `2018-06-09` (enum) - Date to look for movie showtimes.
                    + Members
                        - `today`
                        - `tomorrow`',
                'expected' => [
                    'description' => 'Date to look for movie showtimes.',
                    'field' => 'date',
                    'required' => true,
                    'sample_data' => '2018-06-09',
                    'type' => 'enum',
                    'values' => [
                        'today' => '',
                        'tomorrow' => ''
                    ]
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'invalid-mson' => [
                'annotation' => PathParamAnnotation::class,
                'content' => 'date',
                'expected.exception' => InvalidMSONSyntaxException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'pathparam',
                    'getDocblock' => 'date',
                    'getValues' => []
                ]
            ],
            'unsupported-type' => [
                'annotation' => PathParamAnnotation::class,
                'content' => 'id (str) - Movie ID',
                'expected.exception' => UnsupportedTypeException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'id (str) - Movie ID',
                    'getDocblock' => null
                ]
            ]
        ];
    }
}
