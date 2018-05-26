<?php
namespace Mill\Exceptions\Annotations;

use Mill\Exceptions\BaseException;

class InvalidVendorTagSuppliedException extends BaseException
{
    use AnnotationExceptionTrait;

    /** @var string */
    public $vendor_tag;

    public static function create(
        string $vendor_tag,
        string $class,
        string $method
    ): self {
        $message = sprintf(
            'The vendor tag on `@api-vendortag %s` in %s::%s is not present in your config.',
            $vendor_tag,
            $class,
            $method
        );

        $exception = new self($message);
        $exception->vendor_tag = $vendor_tag;
        $exception->class = $class;
        $exception->method = $method;

        return $exception;
    }

    /**
     * Get the vendor tag that this exception occurred with.
     *
     * @return string
     */
    public function getVendorTag(): string
    {
        return $this->vendor_tag;
    }
}
