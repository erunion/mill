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
             * @api-label The objects's canonical relative URI
             * @api-field uri
             * @api-type uri
             */
            'uri' => '/some/uri/123'
        ];
    }
}
