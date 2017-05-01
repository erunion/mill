<?php
namespace Mill\Examples\Showtimes\Representations;

/**
 * Data representation for a specific movie theater.
 *
 * @api-label Theater
 */
class Theater extends Representation
{
    protected $theater;

    public function create()
    {
        return [
            /**
             * @api-data uri (uri) - Theater URI
             */
            'uri' => $this->theater->uri,

            /**
             * @api-data id (number) - Unique ID
             */
            'id' => $this->theater->id,

            /**
             * @api-data name (string) - Name
             */
            'name' => $this->theater->name,

            /**
             * @api-data address (string) - Address
             */
            'address' => $this->theater->address,

            /**
             * @api-data phone_number (string) - Phone number
             */
            'phone_number' => $this->theater->phone_number,

            /**
             * @api-data website (string) - Website
             * @api-version <1.1
             */
            'website' => $this->theater->website,

            /**
             * @api-data movies (array<\Mill\Examples\Showtimes\Representations\Movie>) - Movies currently playing
             */
            'movies' => $this->theater->getMovies(),

            /**
             * @api-data showtimes (array) - Non-movie specific showtimes
             */
            'showtimes' => $this->theater->getShowtimes()
        ];
    }
}
