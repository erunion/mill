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
     */
    public function testDocumentation(string $class, array $expected): void
    {
        $docs = new Documentation($class, $this->getApplication());

        $this->assertSame($class, $docs->getClass());
        $this->assertCount($expected['methods.size'], $docs->getMethods());

        // Test that it was pulled out of the local cache.
        $this->assertCount($expected['methods.size'], $docs->getMethods());

        $this->assertInstanceOf('\Mill\Parser\Resource\Documentation', $docs->parseMethods());

        // Assert that parseMethods() didn't re-parse or mess up the methods we already had.
        $this->assertCount($expected['methods.size'], $docs->getMethods());

        $class_docs = $docs->toArray();

        $this->assertSame($class_docs['class'], $class);

        foreach ($expected['methods.available'] as $method) {
            $this->assertIsArray($class_docs['methods'][$method]);
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
     * This is to test that Documentation::getMethod() properly calls getMethods() the first time any method is
     * pulled off the current class.
     *
     */
    public function testDocumentationAndGetSpecificMethod(): void
    {
        $class = '\Mill\Examples\Showtimes\Controllers\Movie';
        $docs = new Documentation($class, $this->getApplication());

        $this->assertSame($class, $docs->getClass());
        $this->assertInstanceOf('\Mill\Parser\Resource\Action\Documentation', $docs->getMethod('GET'));
    }

    public function providerDocumentation(): array
    {
        return [
            'Movie' => [
                'class' => '\Mill\Examples\Showtimes\Controllers\Movie',
                'expected' => [
                    'methods.size' => 3,
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
}
