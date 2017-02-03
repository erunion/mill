<?php
namespace Mill\Examples\Showtimes\Representations;

/**
 * @api-label Theater
 */
class Theater extends Representation
{
    protected $theater;

    public function create()
    {
        return [
            /**
             * @api-label Unique ID
             * @api-field id
             * @api-type number
             */
            'id' => $this->theater->id,

            /**
             * @api-label Name
             * @api-field name
             * @api-type string
             */
            'name' => $this->theater->name,

            /**
             * @api-label Address
             * @api-field address
             * @api-type string
             */
            'address' => $this->theater->address,

            /**
             * @api-label Phone number
             * @api-field phone_number
             * @api-type string
             */
            'phone_number' => $this->theater->phone_number,

            /**
             * @api-label Website
             * @api-field website
             * @api-type string
             * @api-version <1.1
             */
            'website' => $this->theater->website,

            /**
             * @api-label Movies currently playing
             * @api-field movies
             * @api-type array
             * @api-subtype \Mill\Examples\Showtimes\Representations\Movie
             */
            'movies' => $this->theater->getMovies(),

            /**
             * @api-label Non-movie specific showtimes
             * @api-field showtimes
             * @api-type array
             */
            'showtimes' => $this->theater->getShowtimes()
        ];
    }
}
