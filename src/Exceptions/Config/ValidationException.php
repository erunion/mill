<?php
namespace Mill\Exceptions\Config;

use Mill\Exceptions\BaseException;

class ValidationException extends BaseException
{
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
