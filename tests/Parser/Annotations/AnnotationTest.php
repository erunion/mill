<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\BaseException;
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
     * @throws BaseException
     */
    public function testAnnotationFailsOnInvalidContent(
        string $annotation,
        string $content,
        string $exception,
        array $asserts
    ): void {
        $this->expectException($exception);

        try {
            if ($annotation === DataAnnotation::class) {
                $this->getDataAnnotationFromDocblock($content, __CLASS__);
            } else {
                (new $annotation($content, __CLASS__, __METHOD__))->process();
            }
        } catch (BaseException $e) {
            if (get_class($e) !== $exception) {
                $this->fail('Unrecognized exception (' . get_class($e) . ') thrown.');
            }

            $this->assertExceptionAsserts($e, __CLASS__, __METHOD__, $asserts);
            throw $e;
        }
    }

    abstract public function providerAnnotation(): array;

    abstract public function providerAnnotationFailsOnInvalidContent(): array;
}
