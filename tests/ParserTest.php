<?php
namespace Mill\Tests;

use Mill\Parser;

class ParserTest extends TestCase
{
    use ReaderTestingTrait;

    public function testParseAnnotationsOnClassWithNoMethod()
    {
        $controller = '\Mill\Examples\Showtimes\Controllers\Movie';
        $docs = (new Parser($controller))->getAnnotations();

        $this->assertCount(2, $docs);
        $this->assertCount(1, $docs['description']);
        $this->assertCount(1, $docs['label']);

        /** @var \Mill\Parser\Annotations\LabelAnnotation $annotation */
        $annotation = $docs['label'][0];
        $this->assertSame('Movies', $annotation->toArray()['label']);

        /** @var \Mill\Parser\Annotations\DescriptionAnnotation $annotation */
        $annotation = $docs['description'][0];
        $this->assertSame('Information on a specific movie.', $annotation->toArray()['description']);
    }

    /**
     * @dataProvider providerParseAnnotationsOnClassMethod
     */
    public function testParseAnnotationsOnClassMethod($method, $expected)
    {
        $controller = '\Mill\Examples\Showtimes\Controllers\Movie';
        $annotations = (new Parser($controller))->getAnnotations($method);
        if (empty($annotations)) {
            $this->fail('No parsed annotations for ' . $controller);
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

    /**
     * @expectedException \Mill\Exceptions\MethodNotImplementedException
     */
    public function testParseAnnotationsOnClassMethodThatDoesntExist()
    {
        $controller = '\Mill\Examples\Showtimes\Controllers\Movie';
        (new Parser($controller))->getAnnotations('POST');
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
                        'count' => 1
                    ],
                    'uriSegment' => [
                        'class' => '\Mill\Parser\Annotations\UriSegmentAnnotation',
                        'count' => 1
                    ]
                ]
            ],
            'PATCH' => [
                'method' => 'PATCH',
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
                    'minVersion' => [
                        'class' => '\Mill\Parser\Annotations\MinVersionAnnotation',
                        'count' => 1
                    ],
                    'param' => [
                        'class' => '\Mill\Parser\Annotations\ParamAnnotation',
                        'count' => 9
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
