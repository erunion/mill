<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Annotations\MissingRepresentationErrorCodeException;
use Mill\Exceptions\Annotations\UnknownErrorRepresentationException;
use Mill\Exceptions\Annotations\UnknownReturnCodeException;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\Traits\HasHttpCodeResponseTrait;
use Mill\Parser\MSON;
use Mill\Parser\Version;

/**
 * Handler for the `@api-error` annotation.
 *
 */
class ErrorAnnotation extends Annotation
{
    use HasHttpCodeResponseTrait;

    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_VENDOR_TAGS = true;
    const SUPPORTS_VERSIONING = true;

    const REGEX_ERROR_CODE = '/^(\(.*\))/';
    const REGEX_ERROR_HTTP_CODE = '/{([\d]+)}/';
    const REGEX_ERROR_TYPE = '/{([\w\s]+)}/';
    const REGEX_ERROR_SUB_TYPE = '/{([\w\s]+),([\w\s]+)}/';

    const ARRAYABLE = [
        'description',
        'error_code',
        'http_code',
        'representation'
    ];

    /**
     * Optional unique error code for the error that this exception handles.
     *
     * @var false|null|string
     */
    protected $error_code = null;

    /**
     * Description for why this exception can be triggered.
     *
     * @var string
     */
    protected $description;

    /**
     * {@inheritdoc}
     * @throws UnknownReturnCodeException If a supplied HTTP code is invalid.
     * @throws UnknownErrorRepresentationException If a supplied representation has not been configured as allowing
     *      errors.
     * @throws MissingRepresentationErrorCodeException If a supplied representation has been configured as requiring
     *      an error code, but is missing it.
     */
    protected function parser(): array
    {
        $config = Container::getConfig();
        $content = trim($this->docblock);

        /** @var string $method */
        $method = $this->method;
        $mson = (new MSON($this->class, $method))->allowAllSubtypes()->parse($content);
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
                        $tag,
                        $this->class,
                        $method
                    ))->process();
                },
                $parsed['vendor_tags']
            );
        }

        if (!empty($parsed['description'])) {
            if (preg_match(self::REGEX_ERROR_SUB_TYPE, $parsed['description'], $matches)) {
                $parsed['description'] = sprintf('If %s was not found in the %s.', $matches[1], $matches[2]);
            } elseif (preg_match(self::REGEX_ERROR_TYPE, $parsed['description'], $matches)) {
                $parsed['description'] = sprintf('If %s was not found.', $matches[1]);
            }
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
     * {@inheritdoc}
     */
    public static function hydrate(array $data = [], Version $version = null)
    {
        /** @var ErrorAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setDescription($data['description']);
        $annotation->setErrorCode($data['error_code']);
        $annotation->setHttpCode($data['http_code']);
        $annotation->setRepresentation($data['representation']);

        return $annotation;
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
     * @return self
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
     * @return self
     */
    public function setErrorCode($error_code): self
    {
        $this->error_code = $error_code;
        return $this;
    }
}