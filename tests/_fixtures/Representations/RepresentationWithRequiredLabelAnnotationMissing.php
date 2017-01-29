<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @deprecated
 */
class RepresentationWithRequiredLabelAnnotationMissing
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
