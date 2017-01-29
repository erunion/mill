<?php
namespace Mill\Parser;

use Mill\Exceptions\Version\BadRangeUseException;
use Mill\Exceptions\Version\LopsidedRangeException;
use Mill\Exceptions\Version\OperatorsWithinRangeException;
use Mill\Exceptions\Version\UnrecognizedSchemaException;

/**
 * Version parsing class for realizing a `@api-version` annotation.
 *
 */
class Version
{
    /**
     * Regex for parsing a modified subset of the Composer version constraints.
     *
     * Currently supports:
     *   - 3.4
     *   - >3.4
     *   - >=3.4
     *   - <3.4
     *   - <=3.3
     *   - 3.0 - 3.3
     *   - ~3.3
     *   - 3.*
     *
     * @link https://getcomposer.org/doc/articles/versions.md
     */
    const VERSION_REGEX = '/^(>=|>|<=|<|~)?(\d\.(\d|\*))( - (\d(\.\d|\*)))?$/';
    const VERSION_FLOAT_REGEX = '(\d\.(\d|\*))';
    const VERSION_CONSTRAINT_REGEX = '(>=|>|<=|<|~)';

    /**
     * @var string|float|int
     */
    protected $version;

    /**
     * @var string|float|int
     */
    protected $version_start;

    /**
     * @var string|float|int
     */
    protected $version_end;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $method;

    /**
     * @param string $version
     * @param string $class
     * @param string $method
     * @throws UnrecognizedSchemaException If an `@api-version` annotation was found with an unrecognized schema.
     */
    public function __construct($version, $class, $method)
    {
        $this->version = $version;
        $this->class = $class;
        $this->method = $method;

        if (!preg_match(self::VERSION_REGEX, $this->version, $matches)) {
            throw UnrecognizedSchemaException::create($this->version, $this->class, $this->method);
        }

        // Is this a range?
        if (strpos($this->version, ' - ') !== false) {
            $this->parseVersionRange($this->version);
        } else {
            $this->parseVersion($this->version);
        }

        if ($this->version_start !== '*') {
            $this->version_start = sprintf('%.1f', $this->version_start);
        }

        if ($this->version_end !== '*') {
            $this->version_end = sprintf('%.1f', $this->version_end);
        }
    }

    /**
     * Parse a `@api-version` annotation block constraint range.
     *
     * Examples:
     *   - 3.0 - 3.3
     *
     * @param string $version
     * @return void
     * @throws OperatorsWithinRangeException If operators (>, >=, <, <=, ~) were found in the range.
     * @throws LopsidedRangeException If a range is lopsided (end is less than the start).
     * @throws BadRangeUseException If a range would be better off as a simple constraint.
     */
    private function parseVersionRange($version)
    {
        // Verify that our range is clean, and free of any operator constraints.
        $range = explode(' - ', $version);
        foreach ($range as $constraint) {
            $regex = '/^' . self::VERSION_CONSTRAINT_REGEX . self::VERSION_FLOAT_REGEX . '/';
            if (preg_match($regex, $constraint)) {
                throw OperatorsWithinRangeException::create($version, $this->class, $this->method);
            }
        }

        $start = array_shift($range);
        $end = array_shift($range);
        if ($end < $start) {
            throw LopsidedRangeException::create($version, $this->class, $this->method);
        } elseif ($start == $end) {
            throw BadRangeUseException::create($version, $start, $this->class, $this->method);
        }

        $this->version_start = $start;
        $this->version_end = $end;
    }

    /**
     * Parse a `@api-version` annotation block constraint.
     *
     * Examples:
     *   - 3.4
     *   - >3.4
     *   - >=3.4
     *   - <3.4
     *   - <=3.3
     *   - ~3.3
     *   - 3.*
     *
     * @param string $version
     * @return void
     */
    private function parseVersion($version)
    {
        // If the version has an operator constraint, let's parse it out and realize the supported versions.
        $regex = '/^' . self::VERSION_CONSTRAINT_REGEX . self::VERSION_FLOAT_REGEX . '/';
        if (preg_match($regex, $version, $matches)) {
            $float = $matches[2];
            switch ($matches[1]) {
                // Matches `>3.4` to to `3.5 - *`.
                case '>':
                    $start = $float + 0.1;
                    $end = '*';
                    break;

                // Matches `>=3.4` to `3.4 - *`.
                case '>=':
                    $start = $float;
                    $end = '*';
                    break;

                // Matches `<3.4` to `* - 3.3`.
                case '<':
                    $start = '*';
                    $end = $float - 0.1;
                    break;

                // Matches '<=3.4' to `* - 3.4`
                case '<=':
                    $start = '*';
                    $end = $float;
                    break;

                // Matches `~3.3` to `3.3 - 4.0`.
                default:
                    $start = $float;
                    $end = ceil($float);
                    break;
            }
        } elseif (strpos($version, '.*') !== false) {
            // Matches `3.*` to `>=3.0 - 4.0`
            $start = (float) str_replace('.*', '.0', $version);
            $end = $start + 1;
        } else {
            // Matches `3.4` to `3.4 - 3.4`.
            $start = $end = $version;
        }

        $this->version_start = $start;
        $this->version_end = $end;
    }

    /**
     * Get the starting point for this version set.
     *
     * @return string|float|int
     */
    public function getStart()
    {
        return $this->version_start;
    }

    /**
     * Get the ending point for this version set.
     *
     * @return string|float|int
     */
    public function getEnd()
    {
        return $this->version_end;
    }

    /**
     * Assert that a given version string matches the current parsed range.
     *
     * @param string $version
     * @return bool
     */
    public function matches($version)
    {
        $start = $this->getStart();
        $end = $this->getEnd();

        // Assert `3.4` matches itself.
        if ($start === $end && $version == $start) {
            return true;
        }

        // Assert `>3.4` and `>=3.4` matches above.
        if ($end === '*' && $version >= $start) {
            return true;
        }

        // Assert `<3.4` and `<=3.4` matches below.
        if ($start === '*' && $version <= $end) {
            return true;
        }

        // Assert ranges, `3.0 - 3.3`, `~3.3`, and ``3.*`` match within those constraints.
        if ($version >= $start && $version <= $end) {
            return true;
        }

        return false;
    }

    /**
     * Convert the parsed version into an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'start' => $this->getStart(),
            'end' => $this->getEnd()
        ];
    }
}
