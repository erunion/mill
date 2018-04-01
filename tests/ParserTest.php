<?php
namespace Mill\Tests;

use Mill\Exceptions\MethodNotImplementedException;
use Mill\Parser;

class ParserTest extends TestCase
{
    use ReaderTestingTrait;

    public function testParseAnnotationsOnClassWithNoMethod(): void
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
     */
    public function testParseAnnotationsOnClassMethod(string $method, array $expected): void
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';
        $annotations = (new Parser($class))->getAnnotations($method);
        if (empty($annotations)) {
            $this->fail('No parsed annotations for ' . $class);
        }

        foreach ($annotations as $annotation => $data) {
            if (!isset($expected[$annotation])) {
                $this->fail('A parsed `' . $annotation . '` annotation was not present in the expected data.');
            }

            $this->assertCount($expected[$annotation]['count'], $data, '`' . $annotation . '` mismatch');

            foreach ($data as $obj) {
                $this->assertInstanceOf($expected[$annotation]['class'], $obj, '`' . $annotation . '` mismatch');
            }
        }
    }

    public function testParsingADeprecatedDecorator(): void
    {
        $this->overrideReadersWithFakeDocblockReturn('/**
          * @api-label Update a piece of content.
          * @api-group Foo\Bar
          *
          * @api-uri:public /foo
          * @api-uri:private:deprecated /bar
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

    public function testParseAnnotationsOnClassMethodThatDoesntExist(): void
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';

        try {
            (new Parser($class))->getAnnotations('POST');
        } catch (MethodNotImplementedException $e) {
            $this->assertSame($class, $e->getClass());
            $this->assertSame('POST', $e->getMethod());
        }
    }

    public function providerParseAnnotationsOnClassMethod(): array
    {
        return [
            'GET' => [
                'method' => 'GET',
                'expected' => [
                    'contentType' => [
                        'class' => Parser\Annotations\ContentTypeAnnotation::class,
                        'count' => 2
                    ],
                    'description' => [
                        'class' => Parser\Annotations\DescriptionAnnotation::class,
                        'count' => 1
                    ],
                    'error' => [
                        'class' => Parser\Annotations\ErrorAnnotation::class,
                        'count' => 3
                    ],
                    'group' => [
                        'class' => Parser\Annotations\GroupAnnotation::class,
                        'count' => 1
                    ],
                    'label' => [
                        'class' => Parser\Annotations\LabelAnnotation::class,
                        'count' => 1
                    ],
                    'minVersion' => [
                        'class' => Parser\Annotations\MinVersionAnnotation::class,
                        'count' => 1
                    ],
                    'return' => [
                        'class' => Parser\Annotations\ReturnAnnotation::class,
                        'count' => 2
                    ],
                    'uri' => [
                        'class' => Parser\Annotations\UriAnnotation::class,
                        'count' => 2
                    ],
                    'uriSegment' => [
                        'class' => Parser\Annotations\UriSegmentAnnotation::class,
                        'count' => 2
                    ]
                ]
            ],
            'PATCH' => [
                'method' => 'PATCH',
                'expected' => [
                    'contentType' => [
                        'class' => Parser\Annotations\ContentTypeAnnotation::class,
                        'count' => 2
                    ],
                    'description' => [
                        'class' => Parser\Annotations\DescriptionAnnotation::class,
                        'count' => 1
                    ],
                    'error' => [
                        'class' => Parser\Annotations\ErrorAnnotation::class,
                        'count' => 6
                    ],
                    'group' => [
                        'class' => Parser\Annotations\GroupAnnotation::class,
                        'count' => 1
                    ],
                    'label' => [
                        'class' => Parser\Annotations\LabelAnnotation::class,
                        'count' => 1
                    ],
                    'minVersion' => [
                        'class' => Parser\Annotations\MinVersionAnnotation::class,
                        'count' => 1
                    ],
                    'param' => [
                        'class' => Parser\Annotations\ParamAnnotation::class,
                        'count' => 11
                    ],
                    'return' => [
                        'class' => Parser\Annotations\ReturnAnnotation::class,
                        'count' => 2
                    ],
                    'scope' => [
                        'class' => Parser\Annotations\ScopeAnnotation::class,
                        'count' => 1
                    ],
                    'uri' => [
                        'class' => Parser\Annotations\UriAnnotation::class,
                        'count' => 1
                    ],
                    'uriSegment' => [
                        'class' => Parser\Annotations\UriSegmentAnnotation::class,
                        'count' => 1
                    ]
                ]
            ],
            'DELETE' => [
                'method' => 'DELETE',
                'expected' => [
                    'contentType' => [
                        'class' => Parser\Annotations\ContentTypeAnnotation::class,
                        'count' => 1
                    ],
                    'description' => [
                        'class' => Parser\Annotations\DescriptionAnnotation::class,
                        'count' => 1
                    ],
                    'error' => [
                        'class' => Parser\Annotations\ErrorAnnotation::class,
                        'count' => 1
                    ],
                    'group' => [
                        'class' => Parser\Annotations\GroupAnnotation::class,
                        'count' => 1
                    ],
                    'label' => [
                        'class' => Parser\Annotations\LabelAnnotation::class,
                        'count' => 1
                    ],
                    'minVersion' => [
                        'class' => Parser\Annotations\MinVersionAnnotation::class,
                        'count' => 1
                    ],
                    'return' => [
                        'class' => Parser\Annotations\ReturnAnnotation::class,
                        'count' => 1
                    ],
                    'scope' => [
                        'class' => Parser\Annotations\ScopeAnnotation::class,
                        'count' => 1
                    ],
                    'uri' => [
                        'class' => Parser\Annotations\UriAnnotation::class,
                        'count' => 1
                    ],
                    'uriSegment' => [
                        'class' => Parser\Annotations\UriSegmentAnnotation::class,
                        'count' => 1
                    ],
                    'vendorTag' => [
                        'class' => Parser\Annotations\VendorTagAnnotation::class,
                        'count' => 1
                    ]
                ]
            ]
        ];
    }
}
