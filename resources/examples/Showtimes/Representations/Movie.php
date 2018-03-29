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
             * @api-data uri (uri) - Movie URI
             */
            'uri' => $this->movie->uri,

            /**
             * @api-data id (number) - Unique ID
             */
            'id' => $this->movie->id,

            /**
             * @api-data name (string) - Name
             */
            'name' => $this->movie->name,

            /**
             * @api-data description (string) - Description
             */
            'description' => $this->movie->description,

            /**
             * @api-data runtime (string) - Runtime
             */
            'runtime' => $this->movie->runtime,

            /**
             * @api-data content_rating (enum) - MPAA rating
             *      + Members
             *          - `G`
             *          - `PG`
             *          - `PG-13`
             *          - `R`
             *          - `NC-17`
             *          - `X`
             *          - `NR`
             *          - `UR`
             */
            'rating' => $this->movie->rating,

            /**
             * @api-data genres (array) - Genres
             */
            'genres' => $this->movie->getGenres(),

            /**
             * @api-data director (\Mill\Examples\Showtimes\Representations\Person) - Director
             * @api-scope public
             */
            'director' => $this->movie->director,

            /**
             * @api-data cast (array<\Mill\Examples\Showtimes\Representations\Person>) - Cast
             * @api-scope public
             */
            'cast' => $this->movie->getCast(),

            /**
             * @api-data kid_friendly `0` (boolean) - Kid friendly?
             */
            'kid_friendly' => $this->movie->is_kid_friendly,

            /**
             * @api-data theaters (array<\Mill\Examples\Showtimes\Representations\Theater>) - Theaters the movie is
             *      currently showing in
             */
            'theaters' => $this->movie->getTheaters(),

            /**
             * @api-data showtimes (array) - Non-theater specific showtimes
             */
            'showtimes' => $this->getShowtimes(),

            /**
             * @api-data external_urls (object) - External URLs
             * @api-version >=1.1
             * @api-see self::getExternalUrls external_urls
             */
            'external_urls' => $this->getExternalUrls(),

            /**
             * @api-data rotten_tomatoes_score (number) - Rotten Tomatoes score
             */
            'rotten_tomatoes_score' => $this->rotten_tomatoes_score,

            'purchase' => [
                /**
                 * @api-data purchase.url (string) - URL to purchase the film.
                 */
                'url' => $this->purchase->digital->url,
            ]
        ];
    }

    /**
     * @return array
     */
    private function getExternalUrls()
    {
        return [
            /**
             * @api-data imdb (string) - IMDB URL
             */
            'imdb' => $this->movie->imdb,

            /**
             * @api-data trailer (string) - Trailer URL
             */
            'trailer' => $this->movie->trailer,

            /**
             * @api-data tickets (string, tag:BUY_TICKETS) - Tickets URL
             * @api-version <1.1.3
             */
            'tickets' => $this->movie->tickets_url
        ];
    }
}
