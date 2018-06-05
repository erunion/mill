<?php
namespace Mill\Exceptions\Config;

use Mill\Exceptions\BaseException;

class UnconfiguredRepresentationException extends BaseException
{
    /** @var string */
    public $representation;

    /**
     * @param string $representation
     * @return UnconfiguredRepresentationException
     */
    public static function create(string $representation): UnconfiguredRepresentationException
    {
        $message = sprintf(
            'The `%s` representation is being used, but has not been configured for use (or excluded).',
            $representation
        );

        $exception = new self($message);
        $exception->representation = $representation;

        return $exception;
    }
}
