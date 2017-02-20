<?php
namespace Mill\Examples\Showtimes\Controllers;

/**
 * Information on a specific movie theater.
 *
 * @api-label Movie Theaters
 */
class Theater
{
    /**
     * Return information on a specific movie theater.
     *
     * @api-label Get a single movie theater
     *
     * @api-uri:public {Theaters} /theaters/+id
     * @api-uriSegment {/theaters/+id} {integer} id Theater ID
     *
     * @api-contentType application/json
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Theater
     * @api-return:public {notmodified} If no content has been modified since the supplied Last-Modified header.
     *
     * @api-throws:public {404} \Mill\Examples\Showtimes\Representations\Error If the movie theater could not be
     *      found.
     */
    public function GET()
    {
        //
    }

    /**
     * Update a movie theaters' data.
     *
     * @api-label Update a movie theater
     *
     * @api-uri:public {Theaters} /theaters/+id
     * @api-uriSegment {/theaters/+id} {integer} id Theater ID
     *
     * @api-contentType application/json
     * @api-scope create
     *
     * @api-param:public {string} name Name of the theater.
     * @api-param:public {string} address Theater address
     * @api-param:public {string} phone_number Theater phone number
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Theater
     *
     * @api-throws:public {400} \Mill\Examples\Showtimes\Representations\Error If there is a problem with the
     *      request.
     * @api-throws:public {404} \Mill\Examples\Showtimes\Representations\Error If the movie movie could not be
     *      found.
     */
    public function PATCH()
    {
        //
    }

    /**
     * Delete a movie theater.
     *
     * @api-label Delete a movie movie.
     *
     * @api-uri:private {Theaters} /theaters/+id
     * @api-uriSegment {/theaters/+id} {integer} id Theater ID
     *
     * @api-contentType application/json
     * @api-scope delete
     *
     * @api-return:private {deleted}
     *
     * @api-throws:private {404} \Mill\Examples\Showtimes\Representations\Error If the movie theater could not be
     *      found.
     */
    public function DELETE()
    {
        //
    }
}
