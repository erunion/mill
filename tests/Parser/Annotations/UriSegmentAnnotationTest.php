<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\InvalidMSONSyntaxException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Parser\Annotations\UriSegmentAnnotation;

class UriSegmentAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $uri
     * @param string $segment
     * @param array $expected
     */
    public function testAnnotation(string $uri, string $segment, array $expected): void
    {
        $annotation = new UriSegmentAnnotation($segment, __CLASS__, __METHOD__, null);
        $annotation->process();

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @dataProvider providerAnnotation
     * @param string $uri
     * @param string $segment
     * @param array $expected
     */
    public function testHydrate(string $uri, string $segment, array $expected): void
    {
        $annotation = UriSegmentAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ));

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(UriSegmentAnnotation $annotation, array $expected): void
    {
        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertFalse($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['uri'], $annotation->getUri());
        $this->assertSame($expected['field'], $annotation->getField());
        $this->assertSame($expected['type'], $annotation->getType());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertFalse($annotation->getCapability());
        $this->assertFalse($annotation->getVersion());
        $this->assertEmpty($annotation->getAliases());
    }

    public function providerAnnotation(): array
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

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'invalid-mson' => [
                'annotation' => UriSegmentAnnotation::class,
                'content' => '{/movies/+id}',
                'expected.exception' => InvalidMSONSyntaxException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'urisegment',
                    'getDocblock' => '{/movies/+id}',
                    'getValues' => []
                ]
            ],
            'missing-uri' => [
                'annotation' => UriSegmentAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'uri',
                    'getAnnotation' => 'urisegment',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'unsupported-type' => [
                'annotation' => UriSegmentAnnotation::class,
                'content' => '{/movies/+id} id (str) - Movie ID',
                'expected.exception' => UnsupportedTypeException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'id (str) - Movie ID',
                    'getDocblock' => null
                ]
            ]
        ];
    }
}
