<?php
namespace Mill\Exceptions\Version;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class UnrecognizedSchemaException extends BaseException
{
    /** @var string */
    public $version = null;

    public static function create(string $version, Docblock $docblock): UnrecognizedSchemaException
    {
        $message = sprintf(
            'An `@api-version` annotation on line %s in %s was found with an unrecognized schema of `%s`.',
            $docblock->getLines(),
            $docblock->getFilename(),
            $version
        );

        $exception = new self($message);
        $exception->version = $version;
        $exception->docblock = $docblock;

        return $exception;
    }

    /**
     * Get a clean error message for this exception that can be used in inline-validation use cases.
     *
     * @return string
     */
    public function getValidationMessage(): string
    {
        return sprintf(
            'The supplied version, `%s`, has an unrecognized schema. Please consult the versioning documentation.',
            $this->version
        );
    }

    /**
     * Get the version that an annotation exception was triggered for.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}
