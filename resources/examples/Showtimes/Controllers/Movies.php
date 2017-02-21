<?php
namespace Mill\Examples\Showtimes\Controllers;

/**
 * @api-label Movies
 */
class Movies
{
    /**
     * Returns all movies for a specific location.
     *
     * @api-label Get movies.
     *
     * @api-uri:public {Movies} /movies
     *
     * @api-contentType application/json
     *
     * @api-param:public {string} location Location you want movies for.
     *
     * @api-return:public {collection} \Mill\Examples\Showtimes\Representations\Movie
     *
     * @api-throws:public {400} \Mill\Examples\Showtimes\Representations\Error If the location is invalid.
     */
    public function GET()
    {
        //
    }

    /**
     * Create a new movie.
     *
     * @api-label Create a movie.
     *
     * @api-uri:public {Movies} /movies
     *
     * @api-contentType application/json
     * @api-scope create
     *
     * @api-param:public {string} name Name of the movie.
     * @api-param:public {string} description Description, or tagline, for the movie.
     * @api-param:public {string} runtime (optional) Movie runtime, in `HHhr MMmin` format.
     * @api-param:public {string} content_rating [G|PG|PG-13|R|NC-17|X|NR|UR] (optional) MPAA rating
     * @api-param:public {array} genres (optional) Array of movie genres.
     * @api-param:public {string} director (optional) Name of the director.
     * @api-param:public {array} cast (optional) Array of names of the cast.
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Movie
     *
     * @api-throws:public {400} \Mill\Examples\Showtimes\Representations\Error If there is a problem with the
     *      request.
     * @api-throws:public {400} \Mill\Examples\Showtimes\Representations\Error If the IMDB URL could not be validated.
     *
     * @api-version 1.1
     * @api-param:public {string} imdb (optional) IMDB URL
     * @api-param:public {string} trailer (optional) Trailer URL
     */
    public function POST()
    {
        //
    }
}
