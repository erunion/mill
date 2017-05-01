<?php
namespace Mill\Tests\Parser;

use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Parser\MSON;

/**
 * Since the versioning system is powered by composer/semver, and it has its own test suite, we don't need to do
 * exhaustive testing on our classes; we just that error handling with it is being properly caught.
 *
 * @link https://github.com/composer/semver
 */
class MSONTest extends \Mill\Tests\TestCase
{
    /**
     * @dataProvider providerTestParse
     * @param string $content
     * @param array $expected
     * @return void
     */
    public function testParse($content, array $expected)
    {
        $mson = (new MSON(__CLASS__, __METHOD__))->parse($content);
        $this->assertSame($expected, $mson->toArray());
    }

    /**
     * @return array
     */
    public function providerTestParse()
    {
        return [
            '_complete' => [
                'content' => 'content_rating `G` (string, optional, MOVIE_RATINGS) - MPAA rating
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [
                        'G' => 'G rated',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated'
                    ]
                ]
            ],
            'capability' => [
                'content' => 'content_rating `G` (string, REQUIRED, MOVIE_RATINGS) - MPAA rating',
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'required' => true,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => []
                ]
            ],
            'description-long' => [
                'content' => 'content_rating `G` (string, required) - Voluptate culpa ex, eiusmod rump sint id. Venison
                    non ribeye landjaeger laboris, enim jowl culpa meatloaf dolore mollit anim. Bacon shankle eiusmod
                    hamburger enim. Laboris lorem pastrami t-bone tempor ullamco swine commodo tri-tip in sirloin.',
                'expected' => [
                    'capability' => false,
                    'description' => 'Voluptate culpa ex, eiusmod rump sint id. Venison non ribeye landjaeger ' .
                        'laboris, enim jowl culpa meatloaf dolore mollit anim. Bacon shankle eiusmod hamburger enim. ' .
                        'Laboris lorem pastrami t-bone tempor ullamco swine commodo tri-tip in sirloin.',
                    'field' => 'content_rating',
                    'required' => true,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => []
                ]
            ],
            'description-markdown' => [
                'content' => 'content_rating `G` (string, optional, MOVIE_RATINGS) - This denotes the
                    [MPAA rating](http://www.mpaa.org/film-ratings/) for the movie.
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'description' => 'This denotes the [MPAA rating](http://www.mpaa.org/film-ratings/) for the movie.',
                    'field' => 'content_rating',
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [
                        'G' => 'G rated',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated'
                    ]
                ]
            ],
            'enum-without-descriptions' => [
                'content' => 'is_kid_friendly `yes` (string, optional) - Is this movie kid friendly?
                    + Members
                        - `yes`
                        - `no`',
                'expected' => [
                    'capability' => false,
                    'description' => 'Is this movie kid friendly?',
                    'field' => 'is_kid_friendly',
                    'required' => false,
                    'sample_data' => 'yes',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [
                        'no' => '',
                        'yes' => ''
                    ]
                ]
            ],
            'enum-without-set-default' => [
                'content' => 'content_rating `G` (string, optional) - MPAA rating
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated
                        - `R` - R rated
                        - `NC-17` - NC-17 rated
                        - `X` - X-rated
                        - `NR` - No rating
                        - `UR` - Unrated',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [
                        'G' => 'G rated',
                        'NC-17' => 'NC-17 rated',
                        'NR' => 'No rating',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated',
                        'R' => 'R rated',
                        'UR' => 'Unrated',
                        'X' => 'X-rated'
                    ]
                ]
            ],
            'field-dot-notation' => [
                'content' => 'content.rating `G` (string) - MPAA rating',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content.rating',
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => []
                ]
            ],
            'type-array-with-subtype-object' => [
                'content' => 'websites (array<object>) - The users\' list of websites.',
                'expected' => [
                    'capability' => false,
                    'description' => 'The users\' list of websites.',
                    'field' => 'websites',
                    'required' => false,
                    'sample_data' => false,
                    'subtype' => 'object',
                    'type' => 'array',
                    'values' => []
                ]
            ],
            'type-array-with-subytpe-representation' => [
                'content' => 'cast (array<\Mill\Examples\Showtimes\Representations\Person>) - Cast members',
                'expected' => [
                    'capability' => false,
                    'description' => 'Cast members',
                    'field' => 'cast',
                    'required' => false,
                    'sample_data' => false,
                    'subtype' => '\Mill\Examples\Showtimes\Representations\Person',
                    'type' => 'array',
                    'values' => []
                ]
            ],
            'type-representation' => [
                'content' => 'director (\Mill\Examples\Showtimes\Representations\Person) - Director',
                'expected' => [
                    'capability' => false,
                    'description' => 'Director',
                    'field' => 'director',
                    'required' => false,
                    'sample_data' => false,
                    'subtype' => false,
                    'type' => '\Mill\Examples\Showtimes\Representations\Person',
                    'values' => []
                ]
            ],
            'without-defined-requirement-but-capability' => [
                'content' => 'content_rating `G` (string, MOVIE_RATINGS) - MPAA rating',
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => []
                ]
            ],
            'without-sample-data' => [
                'param' => 'content_rating (string) - MPAA rating',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'required' => false,
                    'sample_data' => false,
                    'subtype' => false,
                    'type' => 'string',
                    'values' => []
                ]
            ]
        ];
    }

    /*public function testMatches()
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

    public function testParseFailsOnBadVersionSchemas()
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
    }*/
}
