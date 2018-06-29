<?php
namespace Mill\Parser\Annotations;

use Composer\Semver\Semver;
use Mill\Exceptions\Annotations\AbsoluteVersionException;
use Mill\Parser\Annotation;
use Mill\Parser\Version;

class MinVersionAnnotation extends Annotation
{
    const ARRAYABLE = [
        'minimum_version'
    ];

    /** @var string */
    protected $minimum_version;

    /**
     * {@inheritdoc}
     * @throws AbsoluteVersionException
     * @throws \Mill\Exceptions\Version\UnrecognizedSchemaException
     */
    protected function parser(): array
    {
        /** @var string $method */
        $method = $this->method;

        $parsed = new Version($this->docblock, $this->class, $method);
        if ($parsed->isRange()) {
            throw AbsoluteVersionException::create('min', $this->docblock, $this->class, $method);
        }

        return [
            'minimum_version' => $parsed->getConstraint()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        // The Version class already does all of our validation, so if we're at this point, we have a good version and
        // don't need to run it through `$this->required()` again.
        $this->minimum_version = $this->parsed_data['minimum_version'];
    }

    /**
     * @return string
     */
    public function getMinimumVersion(): string
    {
        return $this->minimum_version;
    }

    /**
     * @param string $minimum_version
     * @return MinVersionAnnotation
     */
    public function setMinimumVersion(string $minimum_version): self
    {
        $this->minimum_version = $minimum_version;
        return $this;
    }

    /**
     * Assert that a given version string is greater than the minimum version.
     *
     * @param string $version
     * @return bool
     */
    public function matches(string $version): bool
    {
        return Semver::satisfies($version, '>=' . $this->minimum_version);
    }
}
