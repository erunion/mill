<?php
namespace Mill\Examples\Showtimes\Representations;

/**
 * @api-label Error
 */
class Error extends Representation
{
    public function create($error)
    {
        return [
            /**
             * @api-label User-friendly error message
             * @api-field error
             * @api-type string
             */
            'error' => $error
        ];
    }
}
