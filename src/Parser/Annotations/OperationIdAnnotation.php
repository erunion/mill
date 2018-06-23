<?php
namespace Mill\Parser\Annotations;

use Mill\Parser\Annotation;

class OperationIdAnnotation extends Annotation
{
    const ARRAYABLE = [
        'operation_id'
    ];

    /** @var string */
    protected $operation_id;

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        return [
            'operation_id' => $this->docblock
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->operation_id = $this->required('operation_id');
    }

    /**
     * @return string
     */
    public function getOperationId(): string
    {
        return $this->operation_id;
    }

    /**
     * @param string $operation_id
     * @return OperationIdAnnotation
     */
    public function setOperationId(string $operation_id): self
    {
        $this->operation_id = $operation_id;
        return $this;
    }
}
