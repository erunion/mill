<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @deprecated
 * @api-data uri (uri) - The objects's canonical relative URI
 */
class RepresentationWithRequiredLabelAnnotationMissing
{
    public function create(): array
    {
        return [
            /**
             * @api-data uri (uri) - The objects's canonical relative URI
             */
            'uri' => '/some/uri/123'
        ];
    }
}
