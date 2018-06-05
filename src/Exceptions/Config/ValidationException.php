<?php
namespace Mill\Exceptions\Config;

use Mill\Exceptions\BaseException;

class ValidationException extends BaseException
{
    /**
     * @param int $line
     * @param string $message
     * @return ValidationException
     */
    public static function create(int $line, string $message): ValidationException
    {
        $message = sprintf(
            'Error parsing Mill configuration file on line %s: "%s"',
            $line,
            trim($message)
        );

        return new self($message);
    }
}
