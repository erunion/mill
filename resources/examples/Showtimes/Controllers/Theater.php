<?php
namespace Mill\Examples\Showtimes\Controllers;

/**
 * Information on a specific movie theater.
 *
 * These actions will allow you to pull information on a specific movie theater.
 *
 * @api-label Movie Theaters
 */
class Theater
{
    /**
     * Return information on a specific movie theater.
     *
     * @api-label Get a single movie theater.
     *
     * @api-uri:public {Theaters} /theaters/+id
     * @api-uriSegment {/theaters/+id} id (integer) - Theater ID
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Theater
     * @api-return:public {notmodified} If no content has been modified since the supplied Last-Modified header.
     *
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie theater could not be found.
     *
     * @api-version >=1.1.2
     * @api-contentType application/mill.example.theater
     *
     * @api-version <1.1.2
     * @api-contentType application/json
     */
    public function GET()
    {
        //
    }

    /**
     * Update a movie theaters' data.
     *
     * @api-label Update a movie theater.
     *
     * @api-uri:public {Theaters} /theaters/+id
     * @api-uriSegment {/theaters/+id} id (integer) - Theater ID
     *
     * @api-scope create
     *
     * @api-param:public name (string, required) - Name of the theater.
     * @api-param:public address (string, required) - Theater address
     * @api-param:public phone_number (string, required) - Theater phone number
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Theater
     *
     * @api-error:public 400 (\Mill\Examples\Showtimes\Representations\Error) - If there is a problem with the request.
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie movie could not be found.
     *
     * @api-version >=1.1.2
     * @api-contentType application/mill.example.theater
     *
     * @api-version <1.1.2
     * @api-contentType application/json
     * @api-error:public 403 (\Mill\Examples\Showtimes\Representations\CodedError<1337>) - If something cool happened.
     */
    public function PATCH()
    {
        //
    }

    /**
     * Delete a movie theater.
     *
     * @api-label Delete a movie theater.
     *
     * @api-uri:private {Theaters} /theaters/+id
     * @api-uriSegment {/theaters/+id} id (integer) - Theater ID
     *
     * @api-contentType application/json
     * @api-scope delete
     *
     * @api-return:private {deleted}
     *
     * @api-error:private 404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie theater could not be
     *      found.
     */
    public function DELETE()
    {
        //
    }
}
