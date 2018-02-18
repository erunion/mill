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
use Mill\Parser\HTTPResponseMSON;
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
        $mson = (new HTTPResponseMSON($this->application, $this->docblock))->parse($this->content);
        $parsed = [
            'capability' => $mson->getCapability(),
            'description' => $mson->getDescription(),
            'error_code' => $mson->getErrorCode(),
            'http_code' => $mson->getHttpCode(),
            'representation' => $mson->getRepresentation()
        ];

        $parsed['http_code'] .= ' ' . $this->getHttpCodeMessage($parsed['http_code']);

        if (!empty($parsed['representation'])) {
            $representation = $parsed['representation'];

            // Verify that the supplied representation class exists. If it's being excluded, we can just go ahead and
            // set it here anyways, as we'll be looking further up the stack to determine if we should actually parse it
            // for documentation.
            //
            // If the class doesn't exist, this method call will throw an exception back out.
            if (!$this->application->hasRepresentation($representation)) {
                $this->application->trigger(
                    UnknownErrorRepresentationException::create($representation, $this->docblock)
                );
            }

            // If this representation requires an error code (as defined in the config file), but we don't have one,
            // throw an error.
            /*if ($this->config->doesErrorRepresentationNeedAnErrorCode($representation) &&
                empty($parsed['error_code'])
            ) {
                $this->application->trigger(
                    MissingRepresentationErrorCodeException::create($representation, $this->docblock)
                );
            }*/
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
    /*public static function hydrate(array $data = [], Version $version = null)
    {
        // @var ThrowsAnnotation $annotation
        $annotation = parent::hydrate($data, $version);
        $annotation->setDescription($data['description']);
        $annotation->setErrorCode($data['error_code']);
        $annotation->setHttpCode($data['http_code']);
        $annotation->setRepresentation($data['representation']);

        return $annotation;
    }*/

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
