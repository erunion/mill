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
        $response = [
            /**
             * @api-data uri (uri, required) - Movie URI
             */
            'uri' => $this->movie->uri,

            /**
             * @api-data id (number, required) - Unique ID
             */
            'id' => $this->movie->id,

            /**
             * @api-data name (string, required) - Name
             */
            'name' => $this->movie->name,

            /**
             * @api-data description (string, nullable) - Description
             */
            'description' => $this->movie->description ?: null,

            /**
             * @api-data runtime (string, required) - Runtime
             */
            'runtime' => $this->movie->runtime,

            /**
             * @api-data content_rating (enum, required) - MPAA rating
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
             * @api-data genres (array<uri>, required) - Genres
             */
            'genres' => $this->movie->getGenres(),

            /**
             * @api-data director (\Mill\Examples\Showtimes\Representations\Person, required) - Director
             * @api-scope public
             */
            'director' => $this->movie->director,

            /**
             * @api-data cast (array<\Mill\Examples\Showtimes\Representations\Person>, required) - Cast
             * @api-scope public
             */
            'cast' => $this->movie->getCast(),

            /**
             * @api-data kid_friendly `0` (boolean, required) - Kid friendly?
             */
            'kid_friendly' => $this->movie->is_kid_friendly,

            /**
             * @api-data theaters (array<\Mill\Examples\Showtimes\Representations\Theater>, required) - Theaters the
             *      movie is currently showing in
             */
            'theaters' => $this->movie->getTheaters(),

            /**
             * @api-data showtimes (array, required) - Non-theater specific showtimes
             */
            'showtimes' => $this->getShowtimes(),

            /**
             * @api-scope public
             * @api-data external_urls (array<object>) - External URLs
             * @api-version >=1.1
             * @api-see self::getExternalUrls external_urls
             */
            'external_urls' => $this->getExternalUrls(),

            /**
             * @api-data external_urls.imdb (string, required) - IMDB URL
             */
            'imdb' => $this->movie->imdb,

            /**
             * @api-data rotten_tomatoes_score (number, required) - Rotten Tomatoes score
             */
            'rotten_tomatoes_score' => $this->rotten_tomatoes_score
        ];

        if ($this->movie->is_for_sale) {
            $response['purchase'] = [
                /**
                 * @api-data purchase.url (string, nullable) - URL to purchase the film.
                 */
                'url' => $this->purchase->digital->url ?: null,
            ];
        }

        return $response;
    }

    /**
     * @return array
     */
    private function getExternalUrls()
    {
        return [
            /**
             * @api-data trailer (string, required) - Trailer URL
             */
            'trailer' => $this->movie->trailer,

            /**
             * @api-data tickets (string, required, tag:BUY_TICKETS) - Tickets URL
             * @api-version <1.1.3
             */
            'tickets' => $this->movie->tickets_url
        ];
    }
}
