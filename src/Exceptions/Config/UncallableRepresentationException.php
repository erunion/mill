<?php
namespace Mill\Exceptions\Config;

use Mill\Exceptions\BaseException;

class UncallableRepresentationException extends BaseException
{
    /** @var string */
    public $representation;

    /**
     * @param string $representation
     * @return UncallableRepresentationException
     */
    public static function create(string $representation): UncallableRepresentationException
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
