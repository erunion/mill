<?php
namespace Mill\Parser\Annotations;

use Mill\Exceptions\Annotations\AbsoluteMinimumVersionException;
use Mill\Parser\Annotation;
use Mill\Parser\Version;

/**
 * Handler for the `@api-minVersion` annotation.
 *
 * This annotation, and class are named `minVersion` rather than the preferable `minimumVersion` due to a bizarre
 * issue with PHPUnit code coverage where if the full class name is `MinimumVersionAnnotation`, it shows up as having
 * 0% coverage. Change the file to `MinVersionAnnotation` or `MinAnnotation`, and it has 100%.
 *
 * ¯\_(ಠ_ಠ)_/¯
 *
 */
class MinVersionAnnotation extends Annotation
{
    /** @var string */
    protected $minimum_version;

    /**
     * An array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'minimum_version'
    ];

    /**
     * {@inheritdoc}
     * @throws AbsoluteMinimumVersionException If an `@api-minVersion` annotation version is not absolute.
     */
    protected function parser(): array
    {
        $parsed = new Version($this->application, $this->content, $this->docblock);
        if ($parsed->isRange()) {
            $this->application->trigger(
                AbsoluteMinimumVersionException::create($this->content, $this->docblock)
            );
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
     * {@inheritdoc}
     */
    /*public static function hydrate(array $data = [], Version $version = null): self
    {
        // @var MinVersionAnnotation $annotation
        $annotation = parent::hydrate($data, $version);
        $annotation->setMinimumVersion($data['minimum_version']);

        return $annotation;
    }*/

    /**
     * @return string
     */
    public function getMinimumVersion(): string
    {
        return $this->minimum_version;
    }

    /**
     * @param string $minimum_version
     * @return self
     */
    public function setMinimumVersion(string $minimum_version): self
    {
        $this->minimum_version = $minimum_version;
        return $this;
    }
}
