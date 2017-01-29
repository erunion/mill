<?php
namespace Mill\Parser;

use Mill\Tests\Fixtures\AnnotationStub;

class AnnotationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException
     * @expectedExceptionMessageRegExp /test/
     * @expectedExceptionMessageRegExp /stub/
     * @expectedExceptionMessageRegExp /This is a test/
     */
    public function testAnnotationFailsIfARequiredFieldWasNotSuppliedToRequired()
    {
        new AnnotationStub('This is a test', __CLASS__, __METHOD__);
    }
}
