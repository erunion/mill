<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @api-label RepresentationWithDuplicateFields
 */
class RepresentationWithDuplicateFields
{
    public function create()
    {
        return [
            /**
             * @api-label Canonical relative URI
             * @api-field uri
             * @api-type uri
             */
            'uri' => '/some/uri/123',

            /**
             * @api-label Canonical relative URI
             * @api-field uri
             * @api-type uri
             */
            'created_on' => time()
        ];
    }
}
