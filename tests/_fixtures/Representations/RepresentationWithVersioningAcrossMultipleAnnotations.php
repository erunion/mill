<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @api-label Representation
 */
class RepresentationWithVersioningAcrossMultipleAnnotations
{
    public function create()
    {
        /**
         * @api-data unrelated (string) - An piece of data unrelated to the connections.
         */

        /**
         * @api-data connections (object) - Metadata information about this object.
         * @api-version >=3.3
         */

        /**
         * @api-data connections.things (object, FEATURE_FLAG) - Information about this thing.
         * @api-scope public
         * @api-see self::someMethod connections.things
         */
    }

    public function someMethod()
    {
        /**
         * @api-data uri (uri) - URI that resolves to the connection data.
         * @api-version 3.4
         */

        /**
         * @api-data name (string, MOVIE_RATINGS) - Name of a thing.
         */
    }
}
