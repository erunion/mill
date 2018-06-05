<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Annotations\InvalidScopeSuppliedException;
use Mill\Parser\Annotation;
use Mill\Parser\Version;

class ScopeAnnotation extends Annotation
{
    const ARRAYABLE = [
        'description',
        'scope'
    ];

    /** @var string */
    protected $scope;

    /** @var false|null|string Description for why this scope is required. */
    protected $description = null;

    /**
     * {@inheritdoc}
     * @throws InvalidScopeSuppliedException If a supplied scope isn't present in the config file.
     */
    protected function parser(): array
    {
        $parts = explode(' ', $this->docblock);

        $scope = array_shift($parts);
        $description = trim(implode(' ', $parts));

        if (!empty($scope)) {
            // Validate the supplied scope with what has been configured as allowable.
            $scopes = Container::getConfig()->getScopes();
            if (!in_array($scope, $scopes)) {
                /** @var string $method */
                $method = $this->method;
                throw InvalidScopeSuppliedException::create($scope, $this->class, $method);
            }
        }

        return [
            'scope' => $scope,
            'description' => (!empty($description)) ? $description : null
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->scope = $this->required('scope');
        $this->description = $this->optional('description');
    }

    /**
     * {@inheritdoc}
     */
    public static function hydrate(array $data = [], Version $version = null): self
    {
        /** @var ScopeAnnotation $annotation */
        $annotation = parent::hydrate($data, $version);
        $annotation->setScope($data['scope']);
        $annotation->setDescription($data['description']);

        return $annotation;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     * @return ScopeAnnotation
     */
    public function setScope(string $scope): self
    {
        $this->scope = $scope;
        return $this;
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
     * @return ScopeAnnotation
     */
    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }
}
