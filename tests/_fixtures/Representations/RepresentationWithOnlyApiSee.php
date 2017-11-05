<?php
namespace Mill\Tests\Fixtures\Representations;

use Mill\Examples\Showtimes\Representations\Movie;

/**
 * @api-label RepresentationWithOnlyApiSee
 */
class RepresentationWithOnlyApiSee
{
    public function create(): Movie
    {
        /**
         * @api-see \Mill\Examples\Showtimes\Representations\Movie::create
         */
        return (new Movie())->create();
    }
}
