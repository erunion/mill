<?php
namespace Mill\Tests\Fixtures\Representations;

/**
 * @api-label RepresentationWithOnlyApiSee
 */
class RepresentationWithOnlyApiSee
{
    public function create()
    {
        /**
         * @api-see \Mill\Examples\Showtimes\Representations\Movie::create
         */
    }
}
