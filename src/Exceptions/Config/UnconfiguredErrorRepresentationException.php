<?php
namespace Mill\Exceptions\Config;

class UnconfiguredErrorRepresentationException extends \Exception
{
    /**
     * @var string
     */
    public $representation;

    /**
     * @param string $representation
     * @return UnconfiguredErrorRepresentationException
     */
    public static function create($representation)
    {
        $message = sprintf(
            'The `%s` error representation is being used, but has not been configured for use.',
            $representation
        );

        $exception = new self($message);
        $exception->representation = $representation;

        return $exception;
    }
}
