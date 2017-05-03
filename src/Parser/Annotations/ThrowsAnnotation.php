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

/**
 * Handler for the `@api-throws` annotation.
 *
 */
class ThrowsAnnotation extends Annotation
{
    use HasHttpCodeResponseTrait;

    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_VERSIONING = true;
    const SUPPORTS_DEPRECATION = false;

    const REGEX_ERROR_CODE = '/^(\(.*\))/';
    const REGEX_THROW_HTTP_CODE = '/{([\d]+)}/';
    const REGEX_THROW_TYPE = '/{([\w\s]+)}/';
    const REGEX_THROW_SUB_TYPE = '/{([\w\s]+),([\w\s]+)}/';

    /**
     * Optional unique error code for the error that this exception handles.
     *
     * @var string|null
     */
    protected $error_code = null;

    /**
     * Description for why this exception can be thrown.
     *
     * @var string|null
     */
    protected $description = null;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'capability',
        'description',
        'error_code',
        'http_code',
        'representation',
        'visible'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     * @throws UnknownReturnCodeException If a supplied HTTP code is invalid.
     * @throws UncallableErrorCodeException If a supplied error code is uncallable.
     * @throws UnknownErrorRepresentationException If a supplied representation has not been configured as allowing
     *      errors.
     * @throws MissingRepresentationErrorCodeException If a supplied representation has been configured as requiring
     *      an error code, but is missing it.
     */
    protected function parser()
    {
        $config = Container::getConfig();

        $parsed = [];
        $content = trim($this->docblock);

        // Capability is surrounded by +plusses+.
        if (preg_match(self::REGEX_THROW_HTTP_CODE, $content, $matches)) {
            $parsed['http_code'] = $matches[1];

            if (!$this->isValidHttpCode($parsed['http_code'])) {
                throw UnknownReturnCodeException::create('throws', $this->docblock, $this->class, $this->method);
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
                throw UnknownErrorRepresentationException::create($representation, $this->class, $this->method);
            }
        }

        // Error codes are marked with `(\SomeError\Class::CASE)` or `(1337)` parens.
        if (preg_match(self::REGEX_ERROR_CODE, $content, $matches)) {
            $error_code = substr($matches[1], 1, -1);
            if (is_numeric($error_code)) {
                $parsed['error_code'] = $error_code;
            } else {
                if (!defined($error_code)) {
                    throw UncallableErrorCodeException::create($this->docblock, $this->class, $this->method);
                }

                $parsed['error_code'] = constant($error_code);
            }

            $content = trim(preg_replace(self::REGEX_ERROR_CODE, '', $content));
        }

        // Capability is surrounded by +plusses+.
        if (preg_match(self::REGEX_CAPABILITY, $content, $matches)) {
            $capability = substr($matches[1], 1, -1);
            $parsed['capability'] = new CapabilityAnnotation($capability, $this->class, $this->method);

            $content = trim(preg_replace(self::REGEX_CAPABILITY, '', $content));
        }

        $description = trim($content);
        if (!empty($description)) {
            if (preg_match(self::REGEX_THROW_SUB_TYPE, $description, $matches)) {
                $description = sprintf('If the %s cannot be found in the %s.', $matches[1], $matches[2]);
            } elseif (preg_match(self::REGEX_THROW_TYPE, $description, $matches)) {
                $description = sprintf('If the %s cannot be found.', $matches[1]);
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
                    $this->method
                );
            }
        }

        return $parsed;
    }

    /**
     * Interpret the parsed annotation data and set local variables to build the annotation.
     *
     * To facilitate better error messaging, the order in which items are interpreted here should be match the schema
     * of the annotation.
     *
     * @return void
     */
    protected function interpreter()
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
     * Get the description for this exception.
     *
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Return the unique error code for the error that this exception handles.
     *
     * @return null|string
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }
}
