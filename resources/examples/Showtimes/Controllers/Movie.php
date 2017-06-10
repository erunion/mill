<?php
namespace Mill\Examples\Showtimes\Controllers;

/**
 * Information on a specific movie.
 *
 * These actions will allow you to pull information on a specific movie.
 *
 * @api-label Movies
 */
class Movie
{
    /**
     * Return information on a specific movie.
     *
     * @api-label Get a single movie.
     *
     * @api-uri:private:alias {Movies} /movie/+id
     * @api-uriSegment {/movie/+id} id (integer) - Movie ID
     *
     * @api-uri:public {Movies} /movies/+id
     * @api-uriSegment {/movies/+id} id (integer) - Movie ID
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Movie
     * @api-return:public {notmodified} If no content has been modified since the supplied Last-Modified header.
     *
     * @api-throws:public {404} \Mill\Examples\Showtimes\Representations\Error If the movie could not be found.
     *
     * @api-version >=1.1.2
     * @api-contentType application/mill.example.movie
     *
     * @api-version <1.1.2
     * @api-contentType application/json
     *
     * @api-version >=1.1.3
     * @api-throws:public {404} \Mill\Examples\Showtimes\Representations\Error For no reason.
     * @api-throws:public {404} \Mill\Examples\Showtimes\Representations\Error For some other reason.
     */
    public function GET()
    {
        //
    }

    /**
     * Update a movies data.
     *
     * @api-label Update a movie.
     *
     * @api-uri:public {Movies} /movies/+id
     * @api-uriSegment {/movies/+id} id (integer) - Movie ID
     *
     * @api-scope edit
     * @api-minVersion 1.1
     *
     * @api-param:public name (string, required) - Name of the movie.
     * @api-param:public description (string, required) - Description, or tagline, for the movie.
     * @api-param:public runtime (string, optional) - Movie runtime, in `HHhr MMmin` format.
     * @api-param:public content_rating (string, optional) - MPAA rating
     *  + Members
     *      - `G`
     *      - `PG`
     *      - `PG-13`
     *      - `R`
     *      - `NC-17`
     *      - `X`
     *      - `NR`
     *      - `UR`
     * @api-param:public genres (array, optional) - Array of movie genres.
     * @api-param:public trailer (string, optional) - Trailer URL
     * @api-param:public director (string, optional) - Name of the director.
     * @api-param:public cast (array, optional) - Array of names of the cast.
     * @api-param:public is_kid_friendly (boolean, optional) - Is this movie kid friendly?
     * @api-param:public rotten_tomatoes_score (integer, optional) - Rotten Tomatoes score
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Movie
     *
     * @api-throws:public {400} \Mill\Examples\Showtimes\Representations\Error If there is a problem with the
     *      request.
     * @api-throws:public {400} \Mill\Examples\Showtimes\Representations\Error If the IMDB URL could not be validated.
     * @api-throws:public {404} \Mill\Examples\Showtimes\Representations\Error If the movie could not be found.
     *
     * @api-version >=1.1.2
     * @api-contentType application/mill.example.movie
     *
     * @api-version <1.1.2
     * @api-contentType application/json
     *
     * @api-version >=1.1.1
     * @api-param:public imdb (string, optional) - IMDB URL
     *
     * @api-version >=1.1.3
     * @api-return:public {accepted} \Mill\Examples\Showtimes\Representations\Movie
     * @api-throws:public {404} \Mill\Examples\Showtimes\Representations\Error If the trailer URL could not be
     *      validated.
     */
    public function PATCH()
    {
        //
    }

    /**
     * Delete a movie.
     *
     * @api-label Delete a movie.
     *
     * @api-uri:private {Movies} /movies/+id
     * @api-uriSegment {/movies/+id} id (integer) - Movie ID
     *
     * @api-contentType application/json
     * @api-scope delete
     * @api-minVersion 1.1
     *
     * @api-return:private {deleted}
     *
     * @api-throws:private {404} \Mill\Examples\Showtimes\Representations\Error If the movie could not be found.
     */
    public function DELETE()
    {
        //
    }
}
