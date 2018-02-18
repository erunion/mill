<?php
namespace Mill\Parser;

use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Parser\Reader\Docblock;
use Mill\Tests\Fixtures\AnnotationStub;
use Mill\Tests\TestCase;

class AnnotationTest extends TestCase
{
    public function testAnnotationFailsIfARequiredFieldWasNotSuppliedToRequired(): void
    {
        $content = 'This is a test';
        $docblock = new Docblock($content, __FILE__, 0, strlen($content));

        try {
            (new AnnotationStub($this->application, $content, $docblock))->process();
            $this->fail('MissingRequiredFieldException not thrown.');
        } catch (MissingRequiredFieldException $e) {
            $this->assertSame('test', $e->getRequiredField());
            $this->assertSame('stub', $e->getAnnotation());
            $this->assertSame($docblock, $e->getDocblock());

            $this->assertEmpty($e->getValues());
        }
    }
}
