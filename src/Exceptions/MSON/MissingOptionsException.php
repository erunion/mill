<?php
namespace Mill\Exceptions\MSON;

use Mill\Exceptions\BaseException;
use Mill\Parser\Reader\Docblock;

class MissingOptionsException extends BaseException
{
    /** @var null|string */
    public $type = null;

    public static function create(string $type, Docblock $docblock): MissingOptionsException
    {
        $message = sprintf(
            'A MSON type of `%s` on line %s in %s requires accompanying acceptable values.',
            $type,
            $docblock->getLines(),
            $docblock->getFilename()
        );

        $exception = new self($message);
        $exception->type = $type;
        $exception->docblock = $docblock;

        return $exception;
    }

    /**
     * Get the type that this response exception was triggered for.
     *
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }
}
