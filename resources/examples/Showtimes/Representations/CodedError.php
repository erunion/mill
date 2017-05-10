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
             * @api-data error (string) - User-friendly error message
             */
            'error' => $error,

            /**
             * @api-data error_code (number) - Error code
             */
            'error_code' => $error_code
        ];
    }
}
