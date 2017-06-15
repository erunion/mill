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
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_DEPRECATION = false;
    const SUPPORTS_VERSIONING = false;

    /**
     * Minimum version.
     *
     * @var string
     */
    protected $minimum_version;

    /**
     * Return an array of items that should be included in an array representation of this annotation.
     *
     * @var array
     */
    protected $arrayable = [
        'minimum_version'
    ];

    /**
     * Parse the annotation out and return an array of data that we can use to then interpret this annotations'
     * representation.
     *
     * @return array
     * @throws AbsoluteMinimumVersionException If an `@api-minVersion` annotation version is not absolute.
     */
    protected function parser()
    {
        $parsed = new Version($this->docblock, $this->class, $this->method);
        if ($parsed->isRange()) {
            throw AbsoluteMinimumVersionException::create($this->docblock, $this->class, $this->method);
        }

        return [
            'minimum_version' => $parsed->getConstraint()
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
    protected function interpreter()
    {
        // The Version class already does all of our validation, so if we're at this point, we have a good version and
        // don't need to run it through `$this->required()` again.
        $this->minimum_version = $this->parsed_data['minimum_version'];
    }

    /**
     * Get the (absolute) minimum version that this annotation represents.
     *
     * @return string
     */
    public function getMinimumVersion()
    {
        return $this->minimum_version;
    }
}
