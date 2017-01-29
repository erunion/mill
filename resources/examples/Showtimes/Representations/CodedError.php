<?php
namespace Mill\Examples\Showtimes\Representations;

/**
 * @api-label Coded error
 */
class CodedError extends Representation
{
    const DISALLOWED = 666;

    public function create($error, $error_code)
    {
        return [
            /**
             * @api-label User-friendly error message
             * @api-field error
             * @api-type string
             */
            'error' => $error,

            /**
             * @api-label Error code
             * @api-field error_code
             * @api-type number
             */
            'error_code' => $error_code
        ];
    }
}
