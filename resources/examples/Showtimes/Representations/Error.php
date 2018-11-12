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
             * @api-data error (string) - User-friendly error message
             */
            'error' => $error
        ];
    }
}
