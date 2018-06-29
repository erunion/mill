<?php
namespace Mill\Parser\Annotations;

use Composer\Semver\Semver;
use Mill\Exceptions\Annotations\AbsoluteVersionException;
use Mill\Parser\Annotation;
use Mill\Parser\Version;

class MaxVersionAnnotation extends Annotation
{
    const ARRAYABLE = [
        'maximum_version'
    ];

    /** @var string */
    protected $maximum_version;

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
            throw AbsoluteVersionException::create('max', $this->docblock, $this->class, $method);
        }

        return [
            'maximum_version' => $parsed->getConstraint()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        // The Version class already does all of our validation, so if we're at this point, we have a good version and
        // don't need to run it through `$this->required()` again.
        $this->maximum_version = $this->parsed_data['maximum_version'];
    }

    /**
     * @return string
     */
    public function getMaximumVersion(): string
    {
        return $this->maximum_version;
    }

    /**
     * @param string $maximum_version
     * @return MaxVersionAnnotation
     */
    public function setMaximumVersion(string $maximum_version): self
    {
        $this->maximum_version = $maximum_version;
        return $this;
    }

    /**
     * Assert that a given version string is less than the maximum version.
     *
     * @param string $version
     * @return bool
     */
    public function matches(string $version): bool
    {
        return Semver::satisfies($version, '<=' . $this->maximum_version);
    }
}
