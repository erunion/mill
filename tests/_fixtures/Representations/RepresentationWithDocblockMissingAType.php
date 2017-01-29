<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @api-label RepresentationWithDocblockMissingAType
 */
class RepresentationWithDocblockMissingAType
{
    public function create()
    {
        return [
            /**
             * @api-label Canonical relative URI
             * @api-field uri
             */
            'uri' => '/some/uri/123'
        ];
    }
}
