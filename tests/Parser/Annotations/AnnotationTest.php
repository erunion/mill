<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Tests\TestCase;

abstract class AnnotationTest extends TestCase
{
    /**
     * @dataProvider providerAnnotationFailsOnInvalidAnnotations
     */
    public function testAnnotationFailsOnInvalidAnnotations($annotation, $docblock, $exception, $asserts)
    {
        $this->expectException($exception);

        try {
            if ($annotation === '\Mill\Parser\Annotations\FieldAnnotation') {
                $this->getFieldAnnotationFromDocblock($docblock, __CLASS__, __METHOD__);
            } else {
                new $annotation($docblock, __CLASS__, __METHOD__);
            }
        } catch (\Exception $e) {
            if ('\\' . get_class($e) !== $exception) {
                $this->fail('Unrecognized exception (' . get_class($e) . ') thrown.');
            }

            $this->assertExceptionAsserts($e, __CLASS__, __METHOD__, $asserts);
            throw $e;
        }
    }

    /**
     * @return array
     */
    abstract public function providerAnnotation();

    /**
     * @return array
     */
    abstract public function providerAnnotationFailsOnInvalidAnnotations();
}
