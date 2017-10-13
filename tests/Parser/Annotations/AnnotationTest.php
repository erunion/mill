<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\DataAnnotation;
use Mill\Tests\TestCase;

abstract class AnnotationTest extends TestCase
{
    /**
     * @dataProvider providerAnnotationFailsOnInvalidContent
     * @param string $annotation
     * @param string $content
     * @param string $exception
     * @param array $asserts
     * @throws \Exception
     * @return void
     */
    public function testAnnotationFailsOnInvalidContent(
        string $annotation,
        string $content,
        $exception,
        array $asserts
    ): void {
        $this->expectException($exception);

        try {
            if ($annotation === DataAnnotation::class) {
                $this->getDataAnnotationFromDocblock($content, __CLASS__);
            } else {
                (new $annotation($content, __CLASS__, __METHOD__))->process();
            }
        } catch (\Exception $e) {
            if (get_class($e) !== $exception) {
                $this->fail('Unrecognized exception (' . get_class($e) . ') thrown.');
            }

            $this->assertExceptionAsserts($e, __CLASS__, __METHOD__, $asserts);
            throw $e;
        }
    }

    /**
     * @return array
     */
    abstract public function providerAnnotation(): array;

    /**
     * @return array
     */
    abstract public function providerAnnotationFailsOnInvalidContent(): array;
}
