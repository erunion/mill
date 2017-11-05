<?php
namespace Mill\Tests\Parser;

use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Parser\Version;

/**
 * Since the versioning system is powered by composer/semver, and it has its own test suite, we don't need to do
 * exhaustive testing on our classes; we just that error handling with it is being properly caught.
 *
 * @link https://github.com/composer/semver
 */
class VersionTest extends \PHPUnit\Framework\TestCase
{
    public function testParse(): void
    {
        $version = '>3.4';
        $parsed = new Version($version, __CLASS__, __METHOD__);
        $this->assertSame($version, $parsed->getConstraint());
        $this->assertFalse($parsed->isRange());

        $version = '3.0 - 3.3';
        $parsed = new Version($version, __CLASS__, __METHOD__);
        $this->assertSame($version, $parsed->getConstraint());
        $this->assertTrue($parsed->isRange());
    }

    public function testMatches(): void
    {
        $version = '3.*';
        $parsed = new Version($version, __CLASS__, __METHOD__);

        foreach (['3.0', '3.5'] as $good) {
            $this->assertTrue($parsed->matches($good), $good . ' did not successfully match ' . $version);
        }

        foreach (['2.9', '4.0', '4.1'] as $bad) {
            $this->assertFalse($parsed->matches($bad), $bad . ' improperly matched successfully against ' . $version);
        }
    }

    public function testParseFailsOnBadVersionSchemas(): void
    {
        try {
            new Version('', __CLASS__, __METHOD__);
        } catch (UnrecognizedSchemaException $e) {
            $this->assertSame('', $e->getVersion());
            $this->assertNull($e->getAnnotation());
            $this->assertSame(__CLASS__, $e->getClass());
            $this->assertSame(__METHOD__, $e->getMethod());

            $this->assertSame(
                'The supplied version, ``, has an unrecognized schema. Please consult the versioning documentation.',
                $e->getValidationMessage()
            );
        }
    }
}
