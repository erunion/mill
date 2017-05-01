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
             * @api-data uri (uri) - The objects's canonical relative URI
             */
            'uri' => '/some/uri/123'
        ];
    }
}
