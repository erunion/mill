<?php
namespace Mill\Tests\Parser;

use Mill\Application;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Tests\Fixtures\AnnotationStub;

class AnnotationTest extends \PHPUnit\Framework\TestCase
{
    public function testAnnotationFailsIfARequiredFieldWasNotSuppliedToRequired(): void
    {
        $docblock = 'This is a test';

        try {
            $application = new Application('junk.xml');
            (new AnnotationStub($application, $docblock, __CLASS__, __METHOD__))->process();
            $this->fail('MissingRequiredFieldException not thrown.');
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
