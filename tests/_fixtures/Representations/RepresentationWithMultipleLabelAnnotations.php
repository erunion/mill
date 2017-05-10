<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @api-label Representation
 * @api-label Representation
 */
class RepresentationWithMultipleLabelAnnotations
{
    public function create()
    {
        return [
            /**
             * @api-data uri (uri) - The objects's canonical relative URI
             */
            'uri' => '/some/uri/123'
        ];
    }
}
