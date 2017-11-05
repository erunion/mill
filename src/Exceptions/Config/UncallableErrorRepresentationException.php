<?php
namespace Mill\Exceptions\Config;

use Mill\Exceptions\BaseException;

class UncallableErrorRepresentationException extends BaseException
{
    /** @var string */
    public $representation;

    public static function create(string $representation): UncallableErrorRepresentationException
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
