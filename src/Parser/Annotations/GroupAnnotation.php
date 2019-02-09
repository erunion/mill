<?php
namespace Mill\Parser\Annotations;

use Mill\Exceptions\Annotations\InvalidGroupSuppliedException;
use Mill\Parser\Annotation;

class GroupAnnotation extends Annotation
{
    const ARRAYABLE = [
        'group'
    ];

    /** @var string */
    protected $group;

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        $group = trim($this->docblock);

        if (!empty($group)) {
            // Validate the supplied vendor tag with what has been configured as allowable.
            $tags = $this->application->getConfig()->getTags();
            if (!array_key_exists($group, $tags)) {
                /** @var string $method */
                $method = $this->method;
                throw InvalidGroupSuppliedException::create($group, $this->class, $method);
            }
        }

        return [
            'group' => $group
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->group = $this->required('group');
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     * @return GroupAnnotation
     */
    public function setGroup(string $group): self
    {
        $this->group = $group;
        return $this;
    }
}
