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
             * @api-data uri `/theaters/1234` (uri) - Theater URI
             */
            'uri' => $this->theater->uri,

            /**
             * @api-data id `1234` (number) - Unique ID
             */
            'id' => $this->theater->id,

            /**
             * @api-data name `Alamo Drafthouse Cinema - Yonkers` (string) - Name
             */
            'name' => $this->theater->name,

            /**
             * @api-data address `2548 Central Park Ave, Yonkers, NY 10710` (string) - Address
             */
            'address' => $this->theater->address,

            /**
             * @api-data phone_number `(914) 226-3082` (string) - Phone number
             */
            'phone_number' => $this->theater->phone_number,

            /**
             * @api-data website `https://drafthouse.com/yonkers` (string) - Website
             * @api-version <1.1
             */
            'website' => $this->theater->website,

            /**
             * @api-data movies (array<\Mill\Examples\Showtimes\Representations\Movie>) - Movies currently playing
             */
            'movies' => $this->theater->getMovies(),

            /**
             * @api-data showtimes (array<string>) - Non-movie specific showtimes
             */
            'showtimes' => $this->theater->getShowtimes()
        ];
    }
}
