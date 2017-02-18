<?php
namespace Mill\Tests\Parser\Representation\Types;

use Mill\Tests\TestCase;

abstract class TypeTest extends TestCase
{
    /**
     * @dataProvider providerAnnotationWithType
     */
    public function testAnnotationWithType($docblock, $expected)
    {
        $annotation = $this->getFieldAnnotationFromDocblock($docblock);

        $this->assertFalse($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertSame($expected['field'], $annotation->getFieldName());
        $this->assertSame($expected, $annotation->toArray());
    }

    /**
     * @return array
     */
    abstract public function providerAnnotationWithType();
}
