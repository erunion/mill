<?php
namespace Mill\Parser\Annotations;

use Mill\Exceptions\Annotations\UnknownRepresentationException;
use Mill\Exceptions\Annotations\UnknownReturnCodeException;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Parser\Annotation;
use Mill\Parser\Annotations\Traits\HasHttpCodeResponseTrait;
use Mill\Parser\MSON;

class ReturnAnnotation extends Annotation
{
    use HasHttpCodeResponseTrait;

    const REQUIRES_VISIBILITY_DECORATOR = true;
    const SUPPORTS_VERSIONING = true;

    const ARRAYABLE = [
        'description',
        'http_code',
        'representation',
        'type',
        'visible'
    ];

    /** @var false|null|string Description for what this annotations' action return is. */
    protected $description = null;

    /** @var string Type of object that is being returned for this annotations' action. */
    protected $type;

    /**
     * {@inheritdoc}
     * @throws UnknownRepresentationException
     * @throws UnknownReturnCodeException
     */
    protected function parser(): array
    {
        $config = $this->application->getConfig();
        $content = trim($this->docblock);

        /** @var string $method */
        $method = $this->method;
        try {
            $mson = new MSON($this->class, $method, $config);
            $mson = $mson->parse($content);
        } catch (UnsupportedTypeException $e) {
            throw UnknownRepresentationException::create($content, $this->class, $method);
        }

        $field = $mson->getField();
        $parsed = [
            'type' => $field,
            'description' => $mson->getDescription(),
            'representation' => $mson->getType()
        ];

        if (!empty($field)) {
            $code = $this->findReturnCodeForType($field);
            $parsed['http_code'] = $code . ' ' . $this->getHttpCodeMessage($code);
        }

        return $parsed;
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
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
     * @return int
     * @throws UnknownReturnCodeException If an unrecognized return code is found.
     */
    private function findReturnCodeForType(string $type): int
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
                /** @var string $method */
                $method = $this->method;
                throw UnknownReturnCodeException::create('return', $this->docblock, $this->class, $method);
        }
    }

    /**
     * @return false|null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param false|null|string $description
     * @return ReturnAnnotation
     */
    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ReturnAnnotation
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
}
