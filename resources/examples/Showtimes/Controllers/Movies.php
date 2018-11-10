<?php
namespace Mill\Examples\Showtimes\Controllers;

class Movies
{
    /**
     * Returns all movies for a specific location.
     *
     * @api-label Get movies.
     * @api-operationid getMovies
     * @api-group Movies
     *
     * @api-path:public /movies
     *
     * @api-queryparam:public location (string, required) - Location you want movies for.
     *
     * @api-return:public {collection} \Mill\Examples\Showtimes\Representations\Movie
     *
     * @api-error:public 400 (\Mill\Examples\Showtimes\Representations\Error) - If the location is invalid.
     *
     * @api-version >=1.1.2
     * @api-contentType application/mill.example.movie+json
     *
     * @api-version <1.1.2
     * @api-contentType application/json
     *
     * @api-version >=1.1
     * @api-queryparam:public page (integer, optional) - Page of results to pull.
     */
    public function GET()
    {
        //
    }

    /**
     * Create a new movie.
     *
     * @api-label Create a movie.
     * @api-operationid createMovie
     * @api-group Movies
     *
     * @api-path:public /movies
     *
     * @api-scope create
     *
     * @api-param:public name `Demons` (string, required) - Name of the movie.
     * @api-param:public description (string, required) - Description, or tagline, for the movie.
     * @api-param:public runtime `1hr 20min` (string, optional) - Movie runtime, in `HHhr MMmin` format.
     * @api-param:public content_rating `NR` (enum, optional) - MPAA rating
     *  + Members
     *      - `G` - Rated G
     *      - `PG` - Rated PG
     *      - `PG-13` - Rated PG-13
     *      - `R` - Rated R
     *      - `NC-17` - Rated NC-17
     *      - `X` - Rated X
     *      - `NR` - Not rated
     *      - `UR` - Unrated
     * @api-param:public genres (array<string>, optional) - Array of movie genres.
     * @api-param:public director `Lamberto Bava` (string, optional) - Name of the director.
     * @api-param:public cast (array<object>, optional) - Array of cast members.
     * @api-param:public cast.name `Natasha Hovey` (string, optional) - Cast member name.
     * @api-param:public cast.role `Cheryl` (string, optional) - Cast member role.
     * @api-param:public is_kid_friendly (boolean, optional) - Is this movie kid friendly?
     * @api-param:public rotten_tomatoes_score `56` (integer, optional) - Rotten Tomatoes score
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Movie
     *
     * @api-error:public 400 (\Mill\Examples\Showtimes\Representations\Error) - If there is a problem with the request.
     * @api-error:public 400 (\Mill\Examples\Showtimes\Representations\Error) - If the IMDB URL could not be validated.
     *
     * @api-version >=1.1.2
     * @api-contenttype application/mill.example.movie+json
     *
     * @api-version <1.1.2
     * @api-contenttype application/json
     *
     * @api-version >=1.1
     * @api-param:public imdb `https://www.imdb.com/title/tt0089013/` (string, optional) - IMDB URL
     * @api-param:public trailer `https://www.youtube.com/watch?v=_cNjTdFHL8E` (string, optional, nullable) - Trailer
     *      URL
     *
     * @api-version >=1.1.3
     * @api-return:public {created}
     */
    public function POST()
    {
        //
    }
}
