<?php
namespace Mill\Exceptions\Config;

class UncallableRepresentationException extends \Exception
{
    /**
     * @var string
     */
    public $representation;

    /**
     * @param string $representation
     * @return UncallableRepresentationException
     */
    public static function create($representation)
    {
        $message = sprintf(
            'The `%s` representation is being used, but is uncallable.',
            $representation
        );

        $exception = new self($message);
        $exception->representation = $representation;

        return $exception;
    }
}
