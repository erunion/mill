<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Tests\TestCase;

abstract class AnnotationTest extends TestCase
{
    /**
     * @dataProvider providerAnnotationFailsOnInvalidAnnotations
     */
    public function testAnnotationFailsOnInvalidAnnotations($annotation, $docblock, $exception, $regex = [])
    {
        $this->expectException($exception);
        foreach ($regex as $rule) {
            $this->expectExceptionMessageRegExp($rule);
        }

        new $annotation($docblock, __CLASS__, __METHOD__);
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
