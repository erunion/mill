<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;
use Mill\Parser\Version;

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
        return [
            'group' => $this->docblock
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
