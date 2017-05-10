<?php
namespace Mill\Tests\Parser\Resource;

use Mill\Exceptions\MethodNotImplementedException;
use Mill\Parser\Resource\Documentation;
use Mill\Tests\ReaderTestingTrait;
use Mill\Tests\TestCase;

class DocumentationTest extends TestCase
{
    use ReaderTestingTrait;

    /**
     * @dataProvider providerDocumentation
     * @param string $class
     * @param array $expected
     * @return void
     */
    public function testDocumentation($class, array $expected)
    {
        $docs = (new Documentation($class))->parse();

        $this->assertSame($class, $docs->getClass());
        $this->assertCount($expected['methods.size'], $docs->getMethods());

        // Test that it was pulled out of the local cache.
        $this->assertCount($expected['methods.size'], $docs->getMethods());

        $this->assertInstanceOf('\Mill\Parser\Resource\Documentation', $docs->parseMethods());

        // Assert that parseMethods() didn't re-parse or mess up the methods we already had.
        $this->assertCount($expected['methods.size'], $docs->getMethods());

        $class_docs = $docs->toArray();

        $this->assertSame($class_docs['class'], $class);
        $this->assertSame($expected['label'], $class_docs['label']);
        $this->assertSame($expected['description'], $class_docs['description']);

        foreach ($expected['methods.available'] as $method) {
            $this->assertInternalType('array', $class_docs['methods'][$method]);
            $this->assertInstanceOf('\Mill\Parser\Resource\Action\Documentation', $docs->getMethod($method));
        }

        try {
            $docs->getMethod($expected['method.unavailable']);
            $this->fail();
        } catch (MethodNotImplementedException $e) {
            $this->assertSame($class, $e->getClass());
            $this->assertSame($expected['method.unavailable'], $e->getMethod());
        }
    }

    /**
     * @dataProvider providerDocumentationFailsOnBadClasses
     * @param string $docblock
     * @param string $exception
     * @param array $asserts
     * @throws \Exception
     * @return void
     */
    public function testDocumentationFailsOnBadClasses($docblock, $exception, array $asserts)
    {
        $this->expectException($exception);
        $this->overrideReadersWithFakeDocblockReturn($docblock);

        try {
            (new Documentation(__CLASS__))->parse();
        } catch (\Exception $e) {
            if ('\\' . get_class($e) !== $exception) {
                $this->fail('Unrecognized exception (' . get_class($e) . ') thrown.');
            }

            $this->assertExceptionAsserts($e, __CLASS__, null, $asserts);
            throw $e;
        }
    }

    /**
     * This is to test that Documentation::getMethod() properly calls getMethods() the first time any method is
     * pulled off the current class.
     *
     */
    public function testDocumentationAndGetSpecificMethod()
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';
        $docs = (new Documentation($class))->parse();

        $this->assertSame($class, $docs->getClass());
        $this->assertInstanceOf('\Mill\Parser\Resource\Action\Documentation', $docs->getMethod('GET'));
    }

    /**
     * @return array
     */
    public function providerDocumentation()
    {
        return [
            'Movie' => [
                'class' => '\Mill\Examples\Showtimes\Controllers\Movie',
                'expected' => [
                    'methods.size' => 3,
                    'label' => 'Movies',
                    'description' => 'Information on a specific movie.

These actions will allow you to pull information on a specific movie.',
                    'methods.available' => [
                        'GET',
                        'PATCH',
                        'DELETE'
                    ],
                    'method.unavailable' => 'POST'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerDocumentationFailsOnBadClasses()
    {
        return [
            'missing-required-label-annotation' => [
                'docblock' => '/**
                  *
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\RequiredAnnotationException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'label'
                ]
            ],
            'multiple-label-annotations' => [
                'docblock' => '/**
                  * @api-label Something
                  * @api-label Something else
                  */',
                'expected.exception' => '\Mill\Exceptions\Annotations\MultipleAnnotationsException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'label'
                ]
            ]
        ];
    }
}
