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
        $docs = (new Parser($class, $this->getApplication()))->getAnnotations();

        $this->assertCount(0, $docs);
    }

    /**
     * @dataProvider providerParseAnnotationsOnClassMethod
     * @param string $method
     * @param array $expected
     */
    public function testParseAnnotationsOnClassMethod(string $method, array $expected): void
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';
        $annotations = (new Parser($class, $this->getApplication()))->getAnnotations($method);
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
          * @api-group Movies
          *
          * @api-path:public /foo
          * @api-path:private:deprecated /bar
          *
          * @api-contenttype application/json
          * @api-scope public
          *
          * @api-return:public {ok}
          */');

        $annotations = (new Parser(__CLASS__, $this->getApplication()))->getAnnotations(__METHOD__);

        $this->assertArrayHasKey('path', $annotations);
        $this->assertFalse($annotations['path'][0]->isDeprecated());
        $this->assertTrue($annotations['path'][1]->isDeprecated());
    }

    public function testParseAnnotationsOnClassMethodThatDoesntExist(): void
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';

        try {
            (new Parser($class, $this->getApplication()))->getAnnotations('POST');
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
                    'contenttype' => [
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
                    'minversion' => [
                        'class' => Parser\Annotations\MinVersionAnnotation::class,
                        'count' => 1
                    ],
                    'operationid' => [
                        'class' => Parser\Annotations\OperationIdAnnotation::class,
                        'count' => 1
                    ],
                    'path' => [
                        'class' => Parser\Annotations\PathAnnotation::class,
                        'count' => 2
                    ],
                    'pathparam' => [
                        'class' => Parser\Annotations\PathParamAnnotation::class,
                        'count' => 1
                    ],
                    'return' => [
                        'class' => Parser\Annotations\ReturnAnnotation::class,
                        'count' => 2
                    ]
                ]
            ],
            'PATCH' => [
                'method' => 'PATCH',
                'expected' => [
                    'contenttype' => [
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
                    'minversion' => [
                        'class' => Parser\Annotations\MinVersionAnnotation::class,
                        'count' => 1
                    ],
                    'operationid' => [
                        'class' => Parser\Annotations\OperationIdAnnotation::class,
                        'count' => 1
                    ],
                    'param' => [
                        'class' => Parser\Annotations\ParamAnnotation::class,
                        'count' => 13
                    ],
                    'path' => [
                        'class' => Parser\Annotations\PathAnnotation::class,
                        'count' => 1
                    ],
                    'pathparam' => [
                        'class' => Parser\Annotations\PathParamAnnotation::class,
                        'count' => 1
                    ],
                    'return' => [
                        'class' => Parser\Annotations\ReturnAnnotation::class,
                        'count' => 2
                    ],
                    'scope' => [
                        'class' => Parser\Annotations\ScopeAnnotation::class,
                        'count' => 1
                    ]
                ]
            ],
            'DELETE' => [
                'method' => 'DELETE',
                'expected' => [
                    'contenttype' => [
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
                    'maxversion' => [
                        'class' => Parser\Annotations\MaxVersionAnnotation::class,
                        'count' => 1
                    ],
                    'minversion' => [
                        'class' => Parser\Annotations\MinVersionAnnotation::class,
                        'count' => 1
                    ],
                    'operationid' => [
                        'class' => Parser\Annotations\OperationIdAnnotation::class,
                        'count' => 1
                    ],
                    'pathparam' => [
                        'class' => Parser\Annotations\PathParamAnnotation::class,
                        'count' => 1
                    ],
                    'path' => [
                        'class' => Parser\Annotations\PathAnnotation::class,
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
                    'vendortag' => [
                        'class' => Parser\Annotations\VendorTagAnnotation::class,
                        'count' => 1
                    ]
                ]
            ]
        ];
    }
}
