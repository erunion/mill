<?php
namespace Mill\Exceptions\Config;

class UnconfiguredRepresentationException extends \Exception
{
    /**
     * @var string
     */
    public $representation;

    /**
     * @param string $representation
     * @return UnconfiguredRepresentationException
     */
    public static function create($representation)
    {
        $message = sprintf(
            'The `%s` representation is being used, but has not been configured for use (or ignored).',
            $representation
        );

        $exception = new self($message);
        $exception->representation = $representation;

        return $exception;
    }
}
