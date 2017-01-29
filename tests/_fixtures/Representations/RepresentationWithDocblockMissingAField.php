<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @api-label RepresentationWithDocblockMissingAField
 */
class RepresentationWithDocblockMissingAField
{
    public function create()
    {
        return [
            /**
             * @api-label Canonical relative URI
             */
            'uri' => '/some/uri/123'
        ];
    }
}
