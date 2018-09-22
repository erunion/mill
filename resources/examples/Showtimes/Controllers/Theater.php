<?php
namespace Mill\Examples\Showtimes\Controllers;

class Theater
{
    /**
     * Return information on a specific movie theater.
     *
     * @api-label Get a single movie theater.
     * @api-operationid getTheater
     * @api-group Theaters
     *
     * @api-path:public /theaters/+id
     * @api-pathparam id `1234` (integer) - Theater ID
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Theater
     * @api-return:public {notmodified} If no content has been modified since the supplied Last-Modified header.
     *
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie theater could not be found.
     *
     * @api-version >=1.1.2
     * @api-contenttype application/mill.example.theater+json
     *
     * @api-version <1.1.2
     * @api-contenttype application/json
     */
    public function GET()
    {
        //
    }

    /**
     * Update a movie theaters' data.
     *
     * @api-label Update a movie theater.
     * @api-operationid updateTheater
     * @api-group Theaters
     *
     * @api-path:public /theaters/+id
     * @api-pathparam id `1234` (integer) - Theater ID
     *
     * @api-scope create
     *
     * @api-param:public name `Alamo Drafthouse Cinema - Yonkers` (string, required) - Name of the theater.
     * @api-param:public address `2548 Central Park Ave, Yonkers, NY 10710` (string, required) - Theater address
     * @api-param:public phone_number `(914) 226-3082` (string, required) - Theater phone number
     *
     * @api-return:public {object} \Mill\Examples\Showtimes\Representations\Theater
     *
     * @api-error:public 400 (\Mill\Examples\Showtimes\Representations\Error) - If there is a problem with the request.
     * @api-error:public 404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie movie could not be found.
     *
     * @api-version >=1.1.2
     * @api-contenttype application/mill.example.theater+json
     *
     * @api-version <1.1.2
     * @api-contenttype application/json
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
     * @api-operationid deleteTheater
     * @api-group Theaters
     *
     * @api-path:private /theaters/+id
     * @api-pathparam id `1234` (integer) - Theater ID
     *
     * @api-contenttype application/json
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
