<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Annotations\InvalidScopeSuppliedException;
use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-scope` annotation.
 *
 */
class ScopeAnnotation extends Annotation
{
    /** @var string */
    protected $scope;

    /**
     * Description for why this scope is required.
     *
     * @var false|null|string
     */
    protected $description = null;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'description',
        'scope'
    ];

    /**
     * {@inheritdoc}
     * @throws InvalidScopeSuppliedException If a supplied scope isn't present in the config file.
     */
    protected function parser(): array
    {
        $parts = explode(' ', $this->content);

        $scope = array_shift($parts);
        $description = trim(implode(' ', $parts));

        if (!empty($scope)) {
            // Validate the supplied scope with what has been configured as allowable.
            if (!$this->config->getScopes($scope)) {
                $this->application->trigger(InvalidScopeSuppliedException::create($scope, $this->docblock));
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
    /*public static function hydrate(array $data = [], Version $version = null): self
    {
        // @var ScopeAnnotation $annotation
        $annotation = parent::hydrate($data, $version);
        $annotation->setScope($data['scope']);
        $annotation->setDescription($data['description']);

        return $annotation;
    }*/

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     * @return self
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
     * @return self
     */
    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }
}
