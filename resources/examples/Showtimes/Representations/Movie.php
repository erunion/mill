<?php
namespace Mill\Examples\Showtimes\Representations;

/**
 * Data representation for a specific movie.
 *
 * @api-label Movie
 */
class Movie extends Representation
{
    protected $movie;

    public function create()
    {
        return [
            /**
             * @api-label Movie URI
             * @api-field uri
             * @api-type uri
             */
            'uri' => $this->movie->uri,

            /**
             * @api-label Unique ID
             * @api-field id
             * @api-type number
             */
            'id' => $this->movie->id,

            /**
             * @api-label Name
             * @api-field name
             * @api-type string
             */
            'name' => $this->movie->name,

            /**
             * @api-label Description
             * @api-field description
             * @api-type string
             */
            'description' => $this->movie->description,

            /**
             * @api-label Runtime
             * @api-field runtime
             * @api-type string
             */
            'runtime' => $this->movie->runtime,

            /**
             * @api-label MPAA rating
             * @api-field content_rating
             * @api-type enum
             * @api-options [G|PG|PG-13|R|NC-17|X|NR|UR]
             */
            'rating' => $this->movie->rating,

            /**
             * @api-label Genres
             * @api-field genres
             * @api-type array
             */
            'genres' => $this->movie->getGenres(),

            /**
             * @api-label Urls
             * @api-field urls
             * @api-type object
             * @api-version 1.1
             * @api-see \Mill\Examples\Showtimes\Representations\Movie::getUrls urls
             */
            'urls' => $this->getUrls(),

            /**
             * @api-label Director
             * @api-field director
             * @api-type string
             */
            'director' => $this->movie->director,

            /**
             * @api-label Cast
             * @api-field cast
             * @api-type array
             */
            'cast' => $this->movie->getCast(),

            /**
             * @api-label Theaters the movie is currently showing in
             * @api-field theaters
             * @api-type array
             * @api-subtype \Mill\Examples\Showtimes\Representations\Theater
             */
            'theaters' => $this->movie->getTheaters(),

            /**
             * @api-label Non-theater specific showtimes
             * @api-field showtimes
             * @api-type array
             */
            'showtimes' => $this->getShowtimes()
        ];
    }

    /**
     * @return array
     */
    private function getUrls()
    {
        return [
            /**
             * @api-label IMDB URL
             * @api-field imdb
             * @api-type string
             */
            'imdb' => $this->movie->imdb,

            /**
             * @api-label Trailer URL
             * @api-field trailer
             * @api-type string
             */
            'trailer' => $this->movie->trailer,

            /**
             * @api-label Tickets URL
             * @api-field tickets
             * @api-type string
             * @api-capability BUY_TICKETS
             */
            'tickets' => $this->movie->tickets_url
        ];
    }
}
