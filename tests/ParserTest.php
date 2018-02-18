<?php
namespace Mill\Tests;

use Mill\Exceptions\MethodNotImplementedException;
use Mill\Parser;

class ParserTest extends TestCase
{
    use ReaderTestingTrait;

    /**
     * @dataProvider providerParseAnnotationsOnFile
     * @param array $expected_methods
     */
    public function testParseAnnotationsOnFile($file, array $expected_methods): void
    {
        $docs = (new Parser($this->application, $file))->getAnnotations();

        $this->assertCount(4, $docs);

        // top-level block
        $this->assertCount(2, $docs[0]);
        $this->assertCount(1, $docs[0]['description']);
        $this->assertCount(1, $docs[0]['resource']);

        // `@api-method` blocks
        $i = 1;
        foreach ($expected_methods as $method => $method_annotations) {
            $this->assertSame($method, $docs[$i]['method'][0]->getMethod());
            $this->assertSame(array_keys($method_annotations), array_keys($docs[$i]));

            foreach ($method_annotations as $annotation => $assertions) {
                $this->assertInstanceOf(
                    $assertions[0],
                    $docs[$i][$annotation][0],
                    'Mismatch on `' . $annotation . '` instance type in ' . $method
                );

                $this->assertCount(
                    $assertions[1],
                    $docs[$i][$annotation],
                    'Mismatch on `' . $annotation . '` total in ' . $method
                );
            }

            $i++;
        }

        /** @var Parser\Annotations\ResourceAnnotation $annotation */
        $annotation = $docs[0]['resource'][0];
        $this->assertSame('Movies', $annotation->toArray()['name']);

        /** @var Parser\Annotations\DescriptionAnnotation $annotation */
        $annotation = $docs[0]['description'][0];
        $this->assertSame(
            "Information on a specific movie.\n\nThese actions will allow you to pull information on a specific movie.",
            $annotation->toArray()['description']
        );
    }

    /**
     * @ddataProvider providerParseAnnotationsOnClassMethod
     * @param string $method
     * @param array $expected
     */
    /*public function testParseAnnotationsOnClassMethod(string $method, array $expected): void
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
    }*/

    public function testParsingADeprecatedDecorator(): void
    {
        $this->overrideReadersWithFakeDocblockReturn('/**
          * @api-label Update a piece of content.
          *
          * @api-method GET
          * @api-uri:public {Foo\Bar} /foo
          * @api-uri:private:deprecated {Foo\Bar} /bar
          *
          * @api-contentType application/json
          * @api-scope public
          *
          * @api-return:public (ok)
          */');

        $docs = (new Parser($this->application, __FILE__))->getAnnotations();
        $annotations = $docs[0];

        $this->assertArrayHasKey('uri', $annotations);
        $this->assertFalse($annotations['uri'][0]->isDeprecated());
        $this->assertTrue($annotations['uri'][1]->isDeprecated());
    }

    /*public function testParseAnnotationsOnClassMethodThatDoesntExist(): void
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';

        try {
            (new Parser($class))->getAnnotations('POST');
        } catch (MethodNotImplementedException $e) {
            $this->assertSame($class, $e->getClass());
            $this->assertSame('POST', $e->getMethod());
        }
    }*/

    /**
     * @return array
     */
    public function providerParseAnnotationsOnFile(): array
    {
        return [
            'test-movie-controller' => [
                'file' => 'resources/examples/Showtimes/Controllers/Movie.php',
                'methods' => [
                    'GET' => [
                        'label' => [Parser\Annotations\LabelAnnotation::class, 1],
                        'method' => [Parser\Annotations\MethodAnnotation::class, 1],
                        'uri' => [Parser\Annotations\UriAnnotation::class, 2],
                        'uriSegment' => [Parser\Annotations\UriSegmentAnnotation::class, 2],
                        'return' => [Parser\Annotations\ReturnAnnotation::class, 2],
                        'throws' => [Parser\Annotations\ThrowsAnnotation::class, 3],
                        'contentType' => [Parser\Annotations\ContentTypeAnnotation::class, 2],
                        'description' => [Parser\Annotations\DescriptionAnnotation::class, 1]
                    ],
                    'PATCH' => [
                        'label' => [Parser\Annotations\LabelAnnotation::class, 1],
                        'method' => [Parser\Annotations\MethodAnnotation::class, 1],
                        'uri' => [Parser\Annotations\UriAnnotation::class, 1],
                        'uriSegment' => [Parser\Annotations\UriSegmentAnnotation::class, 1],
                        'scope' => [Parser\Annotations\ScopeAnnotation::class, 1],
                        'minVersion' => [Parser\Annotations\MinVersionAnnotation::class, 1],
                        'param' => [Parser\Annotations\ParamAnnotation::class, 11],
                        'return' => [Parser\Annotations\ReturnAnnotation::class, 2],
                        'throws' => [Parser\Annotations\ThrowsAnnotation::class, 6],
                        'contentType' => [Parser\Annotations\ContentTypeAnnotation::class, 2],
                        'description' => [Parser\Annotations\DescriptionAnnotation::class, 1]
                    ],
                    'DELETE' => [
                        'label' => [Parser\Annotations\LabelAnnotation::class, 1],
                        'method' => [Parser\Annotations\MethodAnnotation::class, 1],
                        'uri' => [Parser\Annotations\UriAnnotation::class, 1],
                        'uriSegment' => [Parser\Annotations\UriSegmentAnnotation::class, 1],
                        'contentType' => [Parser\Annotations\ContentTypeAnnotation::class, 1],
                        'capability' => [Parser\Annotations\CapabilityAnnotation::class, 1],
                        'scope' => [Parser\Annotations\ScopeAnnotation::class, 1],
                        'minVersion' => [Parser\Annotations\MinVersionAnnotation::class, 1],
                        'return' => [Parser\Annotations\ReturnAnnotation::class, 1],
                        'throws' => [Parser\Annotations\ThrowsAnnotation::class, 1],
                        'description' => [Parser\Annotations\DescriptionAnnotation::class, 1],
                    ]
                ]
            ]
        ];
    }
}
