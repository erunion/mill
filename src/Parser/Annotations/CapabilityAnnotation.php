<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Exceptions\Annotations\InvalidCapabilitySuppliedException;
use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-capability` annotation.
 *
 */
class CapabilityAnnotation extends Annotation
{
    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'capability'
    ];

    /**
     * {@inheritdoc}
     * @throws InvalidCapabilitySuppliedException If a found capability is not present in your config file.
     */
    protected function parser(): array
    {
        $capability = $this->content;

        if (!empty($capability)) {
            // Validate the supplied capability with what has been configured as allowable.
            if (!in_array($capability, $this->config->getCapabilities())) {
                $this->application->trigger(
                    InvalidCapabilitySuppliedException::create($capability, $this->docblock)
                );
            }
        }

        return [
            'capability' => $capability
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->capability = $this->required('capability');
    }

    /**
     * {@inheritdoc}
     */
    /*public static function hydrate(array $data = [], Version $version = null): self
    {
        return parent::hydrate($data, $version);
    }*/
}
