<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Annotations\UnknownRepresentationException;
use Mill\Exceptions\Annotations\UnknownReturnCodeException;
use Mill\Exceptions\Config\UnconfiguredRepresentationException;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\Traits\HasHttpCodeResponseTrait;

/**
 * Handler for the `@api-return` annotation.
 *
 */
class ReturnAnnotation extends Annotation
{
    use HasHttpCodeResponseTrait;

    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_DEPRECATION = false;
    const SUPPORTS_VERSIONING = true;

    const REGEX_TYPE = '/^({[^}]*})/';

    /**
     * Description for what this annotations' action return is.
     *
     * @var string|null
     */
    protected $description = null;

    /**
     * Type of object that is being returned for this annotations' action.
     *
     * @var string
     */
    protected $type;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'description',
        'http_code',
        'representation',
        'type'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     * @throws UnknownRepresentationException If a supplied representation has not been configured.
     */
    protected function parser()
    {
        $parsed = [];
        $content = trim($this->docblock);

        // Parameter type is surrounded by `{curly braces}`.
        if (preg_match(self::REGEX_TYPE, $content, $matches)) {
            $parsed['type'] = substr($matches[1], 1, -1);

            $code = $this->findReturnCodeForType($parsed['type']);
            $parsed['http_code'] = $code . ' ' . $this->getHttpCodeMessage($code);

            $content = trim(preg_replace(self::REGEX_TYPE, '', $content));
        }

        $parts = explode(' ', $content);
        $representation = array_shift($parts);
        $description = trim(implode(' ', $parts));

        if (!empty($representation)) {
            // If the supplied representation /looks/ like a PHP FQN, then treat it as such, and verify that it's been
            // either configured or ignored.
            if (preg_match('/\\\([\\w]+)/', $representation)) {
                // Verify that the supplied representation class exists. If it's being excluded, we can just go ahead
                // and set it here anyways, as we'll be looking further up the stack to determine if we should actually
                // parse it for documentation.
                //
                // If the class doesn't exist, this method call will throw an exception back out.
                try {
                    Container::getConfig()->doesRepresentationExist($representation);
                } catch (UnconfiguredRepresentationException $e) {
                    throw UnknownRepresentationException::create($representation, $this->class, $this->method);
                }
            } else {
                $description = trim($representation . ' ' . $description);
                $representation = false;
            }
        }

        $parsed['representation'] = $representation;
        $parsed['description'] = (!empty($description)) ? $description : null;

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
        $this->representation = $this->optional('representation');
        $this->type = $this->required('type');

        // Descriptions are only required for non-200 responses.
        if ($this->isNon200HttpCode()) {
            $this->description = $this->required('description');
        } else {
            $this->description = $this->optional('description');
        }
    }

    /**
     * Grab the HTTP code for a given response type.
     *
     * @param string $type
     * @return integer
     * @throws UnknownReturnCodeException If an unrecognized return code is found.
     */
    private function findReturnCodeForType($type)
    {
        switch ($type) {
            case 'collection':
            case 'directory':
            case 'object':
            case 'ok':
                return 200;

            case 'created':
                return 201;

            case 'accepted':
                return 202;

            case 'added':
            case 'deleted':
            case 'exists':
            case 'updated':
                return 204;

            case 'notmodified':
                return 304;

            default:
                throw UnknownReturnCodeException::create('return', $this->docblock, $this->class, $this->method);
        }
    }

    /**
     * Get the description for this response.
     *
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the type of object that is being returned for this response.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
