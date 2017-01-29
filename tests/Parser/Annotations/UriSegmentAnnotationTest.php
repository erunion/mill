<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\UriSegmentAnnotation;

class UriSegmentAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider annotationProvider
     */
    public function testAnnotation($uri, $segment, $expected)
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
    public function annotationProvider()
    {
        return [
            'bare' => [
                'uri' => '/movies/+id',
                'segment' => '{/movies/+id} {string} id Movie ID',
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
                'segment' => '{/movies/+id/showtimes/*date} {string} date [today|tomorrow] Date to look for movie ' .
                    'showtimes.',
                'expected' => [
                    'description' => 'Date to look for movie showtimes.',
                    'field' => 'date',
                    'type' => 'string',
                    'uri' => '/movies/+id/showtimes/*date',
                    'values' => [
                        'today',
                        'tomorrow'
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function badAnnotationProvider()
    {
        return [
            'missing-uri' => [
                'annotation' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`uri`/'
                ]
            ],
            'missing-field-name' => [
                'annotation' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                'docblock' => '{/movies/+id}',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`field`/'
                ]
            ],
            'missing-type' => [
                'annotation' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                'docblock' => '{/movies/+id} id',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`type`/'
                ]
            ],
            'values-are-in-the-wrong-format' => [
                'annotation' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                'docblock' => '{/movies/+id/showtimes/*date} {string} date [today,tomorrow] Date to look for movie ' .
                    'showtimes.',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\BadOptionsListException',
                'expected.exception.regex' => [
                    '/today,tomorrow/'
                ]
            ],
            'missing-description' => [
                'annotation' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                'docblock' => '{/movies/+id} {string} id',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`description`/'
                ]
            ]
        ];
    }
}
