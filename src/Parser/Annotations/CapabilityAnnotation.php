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
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'capability'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     * @throws InvalidCapabilitySuppliedException If a found capability is not present in your config file.
     */
    protected function parser(): array
    {
        $capability = $this->docblock;

        if (!empty($capability)) {
            // Validate the supplied capability with what has been configured as allowable.
            $capabilities = Container::getConfig()->getCapabilities();
            if (!in_array($capability, $capabilities)) {
                /** @var string $method */
                $method = $this->method;
                throw InvalidCapabilitySuppliedException::create($capability, $this->class, $method);
            }
        }

        return [
            'capability' => $capability
        ];
    }

    /**
     * Interpret the parsed annotation data and set local variables to build the annotation.
     *
     * To facilitate better error messaging, the order in which items are interpreted here should be match the schema
     * of the annotation.
     *
     * @return void
     */
    protected function interpreter(): void
    {
        $this->capability = $this->required('capability');
    }

    /**
     * With an array of data that was output from an Annotation, via `toArray()`, hydrate a new Annotation object.
     *
     * @param array $data
     * @param null|Version $version
     * @return self
     */
    public static function hydrate(array $data = [], Version $version = null): self
    {
        return parent::hydrate($data, $version);
    }
}
