<?php
namespace Mill\Tests;

use Mill\Exceptions\MethodNotImplementedException;
use Mill\Parser;

class ParserTest extends TestCase
{
    use ReaderTestingTrait;

    public function testParseAnnotationsOnClassWithNoMethod()
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';
        $docs = (new Parser($class))->getAnnotations();

        $this->assertCount(2, $docs);
        $this->assertCount(1, $docs['description']);
        $this->assertCount(1, $docs['label']);

        /** @var \Mill\Parser\Annotations\LabelAnnotation $annotation */
        $annotation = $docs['label'][0];
        $this->assertSame('Movies', $annotation->toArray()['label']);

        /** @var \Mill\Parser\Annotations\DescriptionAnnotation $annotation */
        $annotation = $docs['description'][0];
        $this->assertSame('Information on a specific movie.

These actions will allow you to pull information on a specific movie.', $annotation->toArray()['description']);
    }

    /**
     * @dataProvider providerParseAnnotationsOnClassMethod
     * @param string $method
     * @param array $expected
     * @return void
     */
    public function testParseAnnotationsOnClassMethod($method, array $expected)
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';
        $annotations = (new Parser($class))->getAnnotations($method);
        if (empty($annotations)) {
            $this->fail('No parsed annotations for ' . $class);
        }

        foreach ($annotations as $annotation => $data) {
            if (!isset($expected[$annotation])) {
                $this->fail('A parsed `' . $annotation . '`` annotation was not present in the expected data.');
            }

            $this->assertCount($expected[$annotation]['count'], $data, '`' . $annotation . '` mismatch');

            foreach ($data as $obj) {
                $this->assertInstanceOf($expected[$annotation]['class'], $obj, '`' . $annotation . '` mismatch');
            }
        }
    }

    public function testParsingADeprecatedDecorator()
    {
        $this->overrideReadersWithFakeDocblockReturn('/**
          * @api-label Update a piece of content.
          *
          * @api-uri:public {Foo\Bar} /foo
          * @api-uri:private:deprecated {Foo\Bar} /bar
          *
          * @api-contentType application/json
          * @api-scope public
          *
          * @api-return:public {ok}
          */');

        $annotations = (new Parser(__CLASS__))->getAnnotations(__METHOD__);

        $this->assertArrayHasKey('uri', $annotations);
        $this->assertFalse($annotations['uri'][0]->isDeprecated());
        $this->assertTrue($annotations['uri'][1]->isDeprecated());
    }

    public function testParseAnnotationsOnClassMethodThatDoesntExist()
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';

        try {
            (new Parser($class))->getAnnotations('POST');
        } catch (MethodNotImplementedException $e) {
            $this->assertSame($class, $e->getClass());
            $this->assertSame('POST', $e->getMethod());
        }
    }

    /**
     * @return array
     */
    public function providerParseAnnotationsOnClassMethod()
    {
        return [
            'GET' => [
                'method' => 'GET',
                'expected' => [
                    'contentType' => [
                        'class' => '\Mill\Parser\Annotations\ContentTypeAnnotation',
                        'count' => 2
                    ],
                    'description' => [
                        'class' => '\Mill\Parser\Annotations\DescriptionAnnotation',
                        'count' => 1
                    ],
                    'label' => [
                        'class' => '\Mill\Parser\Annotations\LabelAnnotation',
                        'count' => 1
                    ],
                    'minVersion' => [
                        'class' => '\Mill\Parser\Annotations\MinVersionAnnotation',
                        'count' => 1
                    ],
                    'return' => [
                        'class' => '\Mill\Parser\Annotations\ReturnAnnotation',
                        'count' => 2
                    ],
                    'throws' => [
                        'class' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                        'count' => 1
                    ],
                    'uri' => [
                        'class' => '\Mill\Parser\Annotations\UriAnnotation',
                        'count' => 2
                    ],
                    'uriSegment' => [
                        'class' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                        'count' => 2
                    ]
                ]
            ],
            'PATCH' => [
                'method' => 'PATCH',
                'expected' => [
                    'contentType' => [
                        'class' => '\Mill\Parser\Annotations\ContentTypeAnnotation',
                        'count' => 2
                    ],
                    'description' => [
                        'class' => '\Mill\Parser\Annotations\DescriptionAnnotation',
                        'count' => 1
                    ],
                    'label' => [
                        'class' => '\Mill\Parser\Annotations\LabelAnnotation',
                        'count' => 1
                    ],
                    'minVersion' => [
                        'class' => '\Mill\Parser\Annotations\MinVersionAnnotation',
                        'count' => 1
                    ],
                    'param' => [
                        'class' => '\Mill\Parser\Annotations\ParamAnnotation',
                        'count' => 11
                    ],
                    'return' => [
                        'class' => '\Mill\Parser\Annotations\ReturnAnnotation',
                        'count' => 1
                    ],
                    'scope' => [
                        'class' => '\Mill\Parser\Annotations\ScopeAnnotation',
                        'count' => 1
                    ],
                    'throws' => [
                        'class' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                        'count' => 3
                    ],
                    'uri' => [
                        'class' => '\Mill\Parser\Annotations\UriAnnotation',
                        'count' => 1
                    ],
                    'uriSegment' => [
                        'class' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                        'count' => 1
                    ]
                ]
            ],
            'DELETE' => [
                'method' => 'DELETE',
                'expected' => [
                    'contentType' => [
                        'class' => '\Mill\Parser\Annotations\ContentTypeAnnotation',
                        'count' => 1
                    ],
                    'description' => [
                        'class' => '\Mill\Parser\Annotations\DescriptionAnnotation',
                        'count' => 1
                    ],
                    'label' => [
                        'class' => '\Mill\Parser\Annotations\LabelAnnotation',
                        'count' => 1
                    ],
                    'return' => [
                        'class' => '\Mill\Parser\Annotations\ReturnAnnotation',
                        'count' => 1
                    ],
                    'scope' => [
                        'class' => '\Mill\Parser\Annotations\ScopeAnnotation',
                        'count' => 1
                    ],
                    'throws' => [
                        'class' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                        'count' => 1
                    ],
                    'uri' => [
                        'class' => '\Mill\Parser\Annotations\UriAnnotation',
                        'count' => 1
                    ],
                    'uriSegment' => [
                        'class' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                        'count' => 1
                    ]
                ]
            ]
        ];
    }
}
