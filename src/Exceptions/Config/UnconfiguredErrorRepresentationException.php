<?php
namespace Mill\Exceptions\Config;

use Mill\Exceptions\BaseException;

class UnconfiguredErrorRepresentationException extends BaseException
{
    /** @var string */
    public $representation;

    /**
     * @param string $representation
     * @return UnconfiguredErrorRepresentationException
     */
    public static function create(string $representation): UnconfiguredErrorRepresentationException
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
