<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Annotations\MissingRepresentationErrorCodeException;
use Mill\Exceptions\Annotations\UnknownErrorRepresentationException;
use Mill\Exceptions\Annotations\UnknownReturnCodeException;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\Traits\HasHttpCodeResponseTrait;
use Mill\Parser\MSON;

class ErrorAnnotation extends Annotation
{
    use HasHttpCodeResponseTrait;

    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_VENDOR_TAGS = true;
    const SUPPORTS_VERSIONING = true;

    const ARRAYABLE = [
        'description',
        'error_code',
        'http_code',
        'representation'
    ];

    /** @var false|null|string Optional unique error code for the error that this exception handles. */
    protected $error_code = null;

    /** @var string Description for why this exception can be triggered. */
    protected $description;

    /**
     * {@inheritdoc}
     * @return array
     * @throws MissingRepresentationErrorCodeException
     * @throws UnknownErrorRepresentationException
     * @throws UnknownReturnCodeException
     * @throws \Mill\Exceptions\Annotations\UnsupportedTypeException
     * @throws \Mill\Exceptions\MSON\ImproperlyWrittenEnumException
     * @throws \Mill\Exceptions\MSON\MissingOptionsException
     */
    protected function parser(): array
    {
        $config = $this->application->getConfig();
        $content = trim($this->docblock);

        /** @var string $method */
        $method = $this->method;
        $mson = (new MSON($this->class, $method, $config))->allowAllSubtypes()->parse($content);
        $parsed = [
            'http_code' => $mson->getField(),
            'representation' => $mson->getType(),
            'error_code' => $mson->getSubtype(),
            'vendor_tags' => $mson->getVendorTags(),
            'description' => $mson->getDescription()
        ];

        if (!empty($parsed['http_code'])) {
            if (!$this->isValidHttpCode($parsed['http_code'])) {
                throw UnknownReturnCodeException::create('error', $this->docblock, $this->class, $method);
            }

            $parsed['http_code'] .= ' ' . $this->getHttpCodeMessage($parsed['http_code']);
        }

        if (!empty($parsed['vendor_tags'])) {
            $parsed['vendor_tags'] = array_map(
                /** @return Annotation */
                function (string $tag) use ($method) {
                    return (new VendorTagAnnotation(
                        $this->application,
                        $tag,
                        $this->class,
                        $method
                    ))->process();
                },
                $parsed['vendor_tags']
            );
        }

        // Now that we've parsed out both the representation and error code, make sure that a representation that
        // requires an error code, actually has one.
        if (!empty($parsed['representation'])) {
            // If this representation requires an error code (as defined in the config file), but we don't have one,
            // throw an error.
            if ($config->doesErrorRepresentationNeedAnErrorCode($parsed['representation']) &&
                empty($parsed['error_code'])
            ) {
                throw MissingRepresentationErrorCodeException::create(
                    $parsed['representation'],
                    $this->class,
                    $method
                );
            }
        }

        return $parsed;
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->http_code = $this->required('http_code');
        $this->representation = $this->required('representation');

        $this->error_code = $this->optional('error_code');
        if ($this->error_code) {
            $this->error_code = (string)$this->error_code;
        }

        $this->vendor_tags = $this->optional('vendor_tags');
        $this->description = $this->required('description');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return ErrorAnnotation
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return false|null|string
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * @param false|null|string $error_code
     * @return ErrorAnnotation
     */
    public function setErrorCode($error_code): self
    {
        $this->error_code = $error_code;
        return $this;
    }
}
