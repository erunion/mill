<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\UriSegmentAnnotation;

class UriSegmentAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $uri
     * @param string $segment
     * @param array $expected
     * @return void
     */
    public function testAnnotation($uri, $segment, array $expected)
    {
        $annotation = new UriSegmentAnnotation($segment, __CLASS__, __METHOD__, null);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($uri, $annotation->getUri());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['field'], $annotation->getField());
        $this->assertSame($expected['type'], $annotation->getType());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertFalse($annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            'bare' => [
                'uri' => '/movies/+id',
                'segment' => '{/movies/+id} id (string) - Movie ID',
                'expected' => [
                    'description' => 'Movie ID',
                    'field' => 'id',
                    'type' => 'string',
                    'uri' => '/movies/+id',
                    'values' => false
                ]
            ],
            '_complete' => [
                'uri' => '/movies/+id/showtimes/*date',
                'segment' => '{/movies/+id/showtimes/*date} date (string) - Date to look for movie showtimes.
                    + Members
                        - `today`
                        - `tomorrow`',
                'expected' => [
                    'description' => 'Date to look for movie showtimes.',
                    'field' => 'date',
                    'type' => 'string',
                    'uri' => '/movies/+id/showtimes/*date',
                    'values' => [
                        'today' => '',
                        'tomorrow' => ''
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidContent()
    {
        return [
            'invalid-mson' => [
                'annotation' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                'content' => '{/movies/+id}',
                'expected.exception' => '\Mill\Exceptions\Annotations\InvalidMSONSyntaxException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'urisegment',
                    'getDocblock' => '{/movies/+id}',
                    'getValues' => []
                ]
            ],
            'missing-uri' => [
                'annotation' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'uri',
                    'getAnnotation' => 'urisegment',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'unsupported-type' => [
                'annotation' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                'content' => '{/movies/+id} id (str) - Movie ID',
                'expected.exception' => '\Mill\Exceptions\Annotations\UnsupportedTypeException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'id (str) - Movie ID',
                    'getDocblock' => null
                ]
            ]
        ];
    }
}
