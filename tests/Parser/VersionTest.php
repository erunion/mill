<?php
namespace Mill\Tests\Parser;

use Mill\Parser\Version;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider parseProvider
     */
    public function testParse($version, $expected)
    {
        $parsed = new Version($version, __CLASS__, __METHOD__);
        $this->assertSame($parsed->toArray(), $expected);
    }

    /**
     * @dataProvider matchesProvider
     */
    public function testMatches($version, $matches)
    {
        $parsed = new Version($version, __CLASS__, __METHOD__);

        foreach ($matches['good'] as $good) {
            $this->assertTrue($parsed->matches($good), $good . ' did not successfully match ' . $version);
        }

        foreach ($matches['bad'] as $bad) {
            $this->assertFalse($parsed->matches($bad), $bad . ' improperly matched successfully against ' . $version);
        }
    }

    /**
     * @dataProvider badMatchesProvider
     */
    public function testParseFailsOnBadVersionSchemas($version, $exception)
    {
        $this->expectException($exception);

        new Version($version, __CLASS__, __METHOD__);
    }

    /**
     * @return array
     */
    public function parseProvider()
    {
        return [
            '`3.4` becomes `3.4 - 3.4`' => [
                'version' => '3.4',
                'expected' => [
                    'start' => '3.4',
                    'end' => '3.4'
                ]
            ],
            '`>3.4` becomes `3.5 - *`' => [
                'version' => '>3.4',
                'expected' => [
                    'start' => '3.5',
                    'end' => '*'
                ]
            ],
            '`>=3.4` becomes `3.4 - *`' => [
                'version' => '>=3.4',
                'expected' => [
                    'start' => '3.4',
                    'end' => '*'
                ]
            ],
            '`<3.4` becomes `* - 3.3`' => [
                'version' => '<3.4',
                'expected' => [
                    'start' => '*',
                    'end' => '3.3'
                ]
            ],
            '`<=3.4` becomes `* - 3.4`' => [
                'version' => '<=3.4',
                'expected' => [
                    'start' => '*',
                    'end' => '3.4'
                ]
            ],
            '`3.0 - 3.3` becomes `3.0 - 3.3`' => [
                'version' => '3.0 - 3.3',
                'expected' => [
                    'start' => '3.0',
                    'end' => '3.3'
                ]
            ],
            '`~3.3` becomes `3.3 - 4.0`' => [
                'version' => '~3.3',
                'expected' => [
                    'start' => '3.3',
                    'end' => '4.0'
                ]
            ],
            '`3.*` becomes `3.0 - 4.0`' => [
                'version' => '3.*',
                'expected'  => [
                    'start' => '3.0',
                    'end' => '4.0'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function matchesProvider()
    {
        return [
            '3.4-matches-itself' => [
                'version' => '3.4',
                'matches' => [
                    'good' => ['3.4'],
                    'bad' => ['3.3', '3.5']
                ],
            ],
            '>3.4-matches-above' => [
                'version' => '>3.4',
                'matches' => [
                    'good' => ['3.5'],
                    'bad' => ['3.4']
                ],
            ],
            '>=3.4-matches-itself-and-above' => [
                'version' => '>=3.4',
                'matches' => [
                    'good' => ['3.4', '3.5'],
                    'bad' => ['3.3']
                ]
            ],
            '<3.4-matches-below' => [
                'version' => '<3.4',
                'matches' => [
                    'good' => ['3.2', '3.3'],
                    'bad' => ['3.4']
                ]
            ],
            '<=3.4-matches-itself-and-below' => [
                'version' => '<=3.4',
                'matches' => [
                    'good' => ['3.3', '3.4'],
                    'bad' => ['3.5']
                ]
            ],
            '3.0-3.3-matches-range' => [
                'version' => '3.0 - 3.3',
                'matches' => [
                    'good' => ['3.0', '3.1', '3.3'],
                    'bad' => ['2.9', '3.4']
                ]
            ],
            '~3.3-matches-range' => [
                'version' => '~3.3',
                'matches' => [
                    'good' => ['3.3', '3.7', '4.0'],
                    'bad' => ['3.2', '4.1']
                ]
            ],
            '3.*-matches-range becomes `3.0 - 4.0`' => [
                'version' => '3.*',
                'matches' => [
                    'good' => ['3.0', '3.5', '4.0'],
                    'bad' => ['2.9', '4.1']
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function badMatchesProvider()
    {
        return [
            'no-version' => [
                '',
                'exception' => '\Mill\Exceptions\Version\UnrecognizedSchemaException'
            ],
            'unrecognized-schema-integer' => [
                'version' => '3',
                'exception' => '\Mill\Exceptions\Version\UnrecognizedSchemaException'
            ],
            'unrecognized-schema-semver' => [
                '3.0.0',
                'exception' => '\Mill\Exceptions\Version\UnrecognizedSchemaException'
            ],
            'unrecognized-schema-carrot-integer' => [
                '^3',
                'exception' => '\Mill\Exceptions\Version\UnrecognizedSchemaException'
            ],
            'unrecognized-schema-carrot' => [
                '^3.0',
                'exception' => '\Mill\Exceptions\Version\UnrecognizedSchemaException'
            ],
            'unrecognized-schema-range-integet-float' => [
                '3 - 3.3',
                'exception' => '\Mill\Exceptions\Version\UnrecognizedSchemaException'
            ],
            'unrecognized-schema-range-without-space' => [
                '3.0-3.3',
                'exception' => '\Mill\Exceptions\Version\UnrecognizedSchemaException'
            ],
            'range-that-should-not-be-a-range' => [
                'version' => '3.2 - 3.2',
                'exception' => '\Mill\Exceptions\Version\BadRangeUseException'
            ],
            'lopsided-range' => [
                'version' => '3.3 - 3.0',
                'exception' => '\Mill\Exceptions\Version\LopsidedRangeException'
            ],
            'operators-are-found-in-a-range' => [
                'version' => '>=3.0 - 3.3',
                'exception' => '\Mill\Exceptions\Version\OperatorsWithinRangeException'
            ]
        ];
    }
}
