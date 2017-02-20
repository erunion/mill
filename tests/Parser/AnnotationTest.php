<?php
namespace Mill\Parser;

use Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException;
use Mill\Tests\Fixtures\AnnotationStub;

class AnnotationTest extends \PHPUnit_Framework_TestCase
{
    public function testAnnotationFailsIfARequiredFieldWasNotSuppliedToRequired()
    {
        $docblock = 'This is a test';

        try {
            new AnnotationStub($docblock, __CLASS__, __METHOD__);
        } catch (MissingRequiredFieldException $e) {
            $this->assertSame('test', $e->getRequiredField());
            $this->assertSame('stub', $e->getAnnotation());
            $this->assertSame($docblock, $e->getDocblock());
            $this->assertSame(__CLASS__, $e->getClass());
            $this->assertSame(__METHOD__, $e->getMethod());

            $this->assertEmpty($e->getValues());
        }
    }
}
