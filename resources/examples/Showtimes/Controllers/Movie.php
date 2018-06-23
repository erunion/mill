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
     * Donec id elit non mi porta gravida at eget metus. Cras mattis consectetur purus sit amet fermentum. Lorem
     * ipsum dolor sit amet, consectetur adipiscing elit. Etiam porta sem malesuada magna mollis euismod. Duis
     * mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Etiam porta
     * sem malesuada magna mollis euismod.
     *
     * ```
     * [
     *   {"id": "fizzbuzz"}
     * ]
     * ```
     *
     * @api-label Get a single movie.
     * @api-operationid getMovie
     * @api-group Movies
     *
     * @api-path:private:alias /movie/+movie_id
     * @api-path:public /movies/+movie_id
     * @api-pathparam movie_id `1234` (integer) - Movie ID
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Movie
     * @api-return:public {notmodified} If no content has been modified since the supplied Last-Modified header.
     *
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie could not be found.
     *
     * @api-version >=1.1.2
     * @api-contenttype application/mill.example.movie+json
     *
     * @api-version <1.1.2
     * @api-contenttype application/json
     *
     * @api-version >=1.1.3
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - For no reason.
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - For some other reason.
     */
    public function GET()
    {
        //
    }

    /**
     * Update a movies data.
     *
     * @api-label Update a movie.
     * @api-operationid updateMovie
     * @api-group Movies
     *
     * @api-path:public /movies/+id
     * @api-pathparam id `1234` (integer) - Movie ID
     *
     * @api-scope edit
     * @api-minversion 1.1
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
     * @api-param:public genres (array, optional) - Array of movie genres.
     * @api-param:public trailer `https://www.youtube.com/watch?v=_cNjTdFHL8E` (string, optional, nullable) - Trailer
     *      URL
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
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie could not be found.
     *
     * @api-version >=1.1.2
     * @api-contenttype application/mill.example.movie+json
     *
     * @api-version <1.1.2
     * @api-contenttype application/json
     *
     * @api-version >=1.1.1
     * @api-param:public imdb `https://www.imdb.com/title/tt0089013/` (string, optional) - IMDB URL
     *
     * @api-version >=1.1.3
     * @api-return:public {accepted} \Mill\Examples\Showtimes\Representations\Movie
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - If the trailer URL could not be
     *      validated.
     * @api-error:private 403 (\Mill\Examples\Showtimes\Representations\CodedError<1337>) - If something cool happened.
     * @api-error:public 403 (\Mill\Examples\Showtimes\Representations\CodedError<666>) - If the user is not allowed to
     *      edit that movie.
     */
    public function PATCH()
    {
        //
    }

    /**
     * Delete a movie.
     *
     * @api-label Delete a movie.
     * @api-operationid deleteMovie
     * @api-group Movies
     *
     * @api-path:private /movies/+id
     * @api-pathparam id `1234` (integer) - Movie ID
     *
     * @api-contenttype application/json
     * @api-vendortag tag:DELETE_CONTENT
     * @api-scope delete
     * @api-minVersion 1.1
     * @api-maxVersion 1.1.2
     *
     * @api-return:private {deleted}
     *
     * @api-error:private 404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie could not be found.
     */
    public function DELETE()
    {
        //
    }
}
