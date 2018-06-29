<?php
namespace Mill\Examples\Showtimes\Controllers;

/**
 * @api-label Movie Theaters
 */
class Theaters
{
    /**
     * Returns all movie theatres for a specific location.
     *
     * @api-label Get movie theaters.
     * @api-operationid getTheaters
     * @api-group Theaters
     *
     * @api-path:public /theaters
     *
     * @api-queryparam:public location (string, required) - Location you want theaters in.
     *
     * @api-return:public {collection} \Mill\Examples\Showtimes\Representations\Theater
     *
     * @api-error:public 400 (\Mill\Examples\Showtimes\Representations\Error) - If the location is invalid.
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
     * Create a new movie theater.
     *
     * @api-label Create a movie theater.
     * @api-operationid createTheater
     * @api-group Theaters
     *
     * @api-path:public /theaters
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
     *
     * @api-version >=1.1.2
     * @api-contenttype application/mill.example.theater+json
     *
     * @api-version <1.1.2
     * @api-contenttype application/json
     */
    public function POST()
    {
        //
    }
}
