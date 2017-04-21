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
     *
     * @api-uri:public {Theaters} /theaters
     *
     * @api-param:public {string} location Location you want theaters in.
     *
     * @api-return:public {collection} \Mill\Examples\Showtimes\Representations\Theater
     *
     * @api-throws:public {400} \Mill\Examples\Showtimes\Representations\Error If the location is invalid.
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
     * Create a new movie theater.
     *
     * @api-label Create a movie theater.
     *
     * @api-uri:public {Theaters} /theaters
     *
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
     *
     * @api-version >=1.1.2
     * @api-contentType application/mill.example.theater
     *
     * @api-version <1.1.2
     * @api-contentType application/json
     */
    public function POST()
    {
        //
    }
}
