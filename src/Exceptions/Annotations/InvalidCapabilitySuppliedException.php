<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class InvalidCapabilitySuppliedException extends BaseException
{
    use AnnotationExceptionTrait;

    /** @var string */
    public $capability;

    public static function create(
        string $capability,
        Docblock $docblock
    ): InvalidCapabilitySuppliedException {
        $message = sprintf(
            'The capability, `%s`, on line %s in %s is not present in your config.',
            $capability,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->capability = $capability;
        $exception->docblock = $docblock;

        return $exception;
    }

    /**
     * Get the capability that this exception occurred with.
     *
     * @return string
     */
    public function getCapability(): string
    {
        return $this->capability;
    }
}
