<?php
namespace Mill\Exceptions\Config;

class ValidationException extends \Exception
{
    /**
     * @param integer $line
     * @param string $message
     * @return ValidationException
     */
    public static function create($line, $message)
    {
        $message = sprintf(
            'Error parsing Mill configuration file on line %s: "%s"',
            $line,
            trim($message)
        );

        return new self($message);
    }
}
