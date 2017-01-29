<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @api-label RepresentationWithDuplicateVersionAnnotations
 */
class RepresentationWithDuplicateVersionAnnotations
{
    public function create()
    {
        return [
            /**
             * @api-label Canonical relative URI
             * @api-field uri
             * @api-type uri
             * @api-version >3.2
             * @api-version 3.4
             */
            'uri' => '/some/uri/123'
        ];
    }
}
