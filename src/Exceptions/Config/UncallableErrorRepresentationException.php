<?php
namespace Mill\Exceptions\Config;

class UncallableErrorRepresentationException extends \Exception
{
    /**
     * @var string
     */
    public $representation;

    /**
     * @param string $representation
     * @return UncallableErrorRepresentationException
     */
    public static function create($representation)
    {
        $message = sprintf(
            'The `%s` error representation is being used, but is uncallable.',
            $representation
        );

        $exception = new self($message);
        $exception->representation = $representation;

        return $exception;
    }
}
