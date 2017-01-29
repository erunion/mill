<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @api-label RepresentationWithDuplicateCapabilityAnnotations
 */
class RepresentationWithDuplicateCapabilityAnnotations
{
    public function create()
    {
        return [
            /**
             * @api-label Canonical relative URI
             * @api-field uri
             * @api-type uri
             * @api-capability SomeCapability
             * @api-capability SomeOtherCapability
             */
            'uri' => '/some/uri/123'
        ];
    }
}
