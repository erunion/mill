<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Annotations\MissingRepresentationErrorCodeException;
use Mill\Exceptions\Annotations\UncallableErrorCodeException;
use Mill\Exceptions\Annotations\UnknownErrorRepresentationException;
use Mill\Exceptions\Annotations\UnknownReturnCodeException;
use Mill\Exceptions\Config\UnconfiguredErrorRepresentationException;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\Traits\HasHttpCodeResponseTrait;
use Mill\Parser\Version;

/**
 * Handler for the `@api-throws` annotation.
 *
 */
class ThrowsAnnotation extends Annotation
{
    use HasHttpCodeResponseTrait;

    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_VERSIONING = true;

    const REGEX_ERROR_CODE = '/^(\(.*\))/';
    const REGEX_THROW_HTTP_CODE = '/{([\d]+)}/';
    const REGEX_THROW_TYPE = '/{([\w\s]+)}/';
    const REGEX_THROW_SUB_TYPE = '/{([\w\s]+),([\w\s]+)}/';

    /**
     * Optional unique error code for the error that this exception handles.
     *
     * @var false|null|string
     */
    protected $error_code = null;

    /**
     * Description for why this exception can be thrown.
     *
     * @var string
     */
    protected $description;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'capability',
        'description',
        'error_code',
        'http_code',
        'representation'
    ];

    /**
     * {@inheritdoc}
     * @throws UnknownReturnCodeException If a supplied HTTP code is invalid.
     * @throws UncallableErrorCodeException If a supplied error code is uncallable.
     * @throws UnknownErrorRepresentationException If a supplied representation has not been configured as allowing
     *      errors.
     * @throws MissingRepresentationErrorCodeException If a supplied representation has been configured as requiring
     *      an error code, but is missing it.
     */
    protected function parser(): array
    {
        $config = Container::getConfig();

        /** @var string $method */
        $method = $this->method;

        $parsed = [];
        $content = trim($this->docblock);

        // HTTP code is surrounded by +plusses+.
        if (preg_match(self::REGEX_THROW_HTTP_CODE, $content, $matches)) {
            $parsed['http_code'] = $matches[1];

            if (!$this->isValidHttpCode($parsed['http_code'])) {
                throw UnknownReturnCodeException::create('throws', $this->docblock, $this->class, $method);
            }

            $parsed['http_code'] .= ' ' . $this->getHttpCodeMessage($parsed['http_code']);
            $content = trim(preg_replace(self::REGEX_THROW_HTTP_CODE, '', $content));
        }

        $parts = explode(' ', $content);
        $parsed['representation'] = array_shift($parts);

        // Representation is by itself, so put the pieces back together so we can do some more regex.
        $content = implode(' ', $parts);

        if (!empty($parsed['representation'])) {
            $representation = $parsed['representation'];

            // Verify that the supplied representation class exists. If it's being excluded, we can just go ahead and
            // set it here anyways, as we'll be looking further up the stack to determine if we should actually parse it
            // for documentation.
            //
            // If the class doesn't exist, this method call will throw an exception back out.
            try {
                $config->doesErrorRepresentationExist($representation);
            } catch (UnconfiguredErrorRepresentationException $e) {
                throw UnknownErrorRepresentationException::create($representation, $this->class, $method);
            }
        }

        // Error codes are marked with `(\SomeError\Class::CASE)` or `(1337)` parens.
        if (preg_match(self::REGEX_ERROR_CODE, $content, $matches)) {
            $error_code = substr($matches[1], 1, -1);
            if (is_numeric($error_code)) {
                $parsed['error_code'] = $error_code;
            } else {
                if (!defined($error_code)) {
                    throw UncallableErrorCodeException::create($this->docblock, $this->class, $method);
                }

                $parsed['error_code'] = constant($error_code);
            }

            $content = trim(preg_replace(self::REGEX_ERROR_CODE, '', $content));
        }

        // Capability is surrounded by +plusses+.
        if (preg_match(self::REGEX_CAPABILITY, $content, $matches)) {
            $capability = substr($matches[1], 1, -1);
            $parsed['capability'] = (new CapabilityAnnotation($capability, $this->class, $method))->process();

            $content = trim(preg_replace(self::REGEX_CAPABILITY, '', $content));
        }

        $description = trim($content);
        if (!empty($description)) {
            if (preg_match(self::REGEX_THROW_SUB_TYPE, $description, $matches)) {
                $description = sprintf('If %s was not found in the %s.', $matches[1], $matches[2]);
            } elseif (preg_match(self::REGEX_THROW_TYPE, $description, $matches)) {
                $description = sprintf('If %s was not found.', $matches[1]);
            }

            $parsed['description'] = $description;
        }

        // Now that we've parsed out both the representation and error code, make sure that a representation that
        // requires an error code, actually has one.
        if (!empty($parsed['representation'])) {
            $representation = $parsed['representation'];

            // If this representation requires an error code (as defined in the config file), but we don't have one,
            // throw an error.
            if ($config->doesErrorRepresentationNeedAnErrorCode($representation) && !isset($parsed['error_code'])) {
                throw MissingRepresentationErrorCodeException::create(
                    $representation,
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

        $this->capability = $this->optional('capability');
        $this->description = $this->required('description');
    }

    /**
     * {@inheritdoc}
     */
    public static function hydrate(array $data = [], Version $version = null)
    {
        /** @var ThrowsAnnotation $annotation */
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
