<?php
namespace Mill\Parser;

use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Mill\Application;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Parser\Reader\Docblock;

/**
 * Version parsing class for realizing a `@api-version` annotation.
 *
 */
class Version
{
    /** @var ConstraintInterface */
    protected $constraint;

    /** @var string */
    protected $docblock;

    /**
     * @param Application $application
     * @param string $constraint
     * @param Docblock $docblock
     * @throws UnrecognizedSchemaException If an `@api-version` annotation was found with an unrecognized schema.
     */
    public function __construct(Application $application, string $constraint, Docblock $docblock)
    {
        $this->docblock = $docblock;

        try {
            $parser = new VersionParser;
            $this->constraint = $parser->parseConstraints($constraint);
        } catch (\UnexpectedValueException $e) {
            $application->trigger(
                UnrecognizedSchemaException::create($constraint, $this->docblock)
            );
        }
    }

    /**
     * Assert that a given version string matches the current parsed range.
     *
     * @param string $version
     * @return bool
     */
    public function matches(string $version): bool
    {
        return Semver::satisfies($version, $this->getConstraint());
    }

    /**
     * @return string
     */
    public function getConstraint(): string
    {
        return $this->constraint->getPrettyString();
    }

    /**
     * Is the parsed version constraint a range (i.e. a multi constraint)?
     *
     * @return bool
     */
    public function isRange(): bool
    {
        return $this->constraint instanceof MultiConstraint;
    }
}
