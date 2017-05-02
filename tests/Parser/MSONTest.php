<?php
namespace Mill\Tests\Parser;

use Mill\Parser\MSON;
use Mill\Tests\TestCase;

class MSONTest extends TestCase
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

    public function testEnumFailsWithoutValues()
    {
        $this->expectException('Mill\Exceptions\MSON\MissingOptionsException');

        $content = 'content_rating (enum) - MPAA rating';
        (new MSON(__CLASS__, __METHOD__))->parse($content);
    }

    /**
     * @dataProvider providerTestParseFailsOnInvalidTypes
     * @param string $content
     * @return void
     */
    public function testParseFailsOnInvalidTypes($content)
    {
        $this->expectException('Mill\Exceptions\Annotations\UnsupportedTypeException');
        (new MSON(__CLASS__, __METHOD__))->parse($content);
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
            'description-starts-on-new-line' => [
                'content' => 'content_rating `G` (string)
                    - MPAA Rating
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'expected' => [
                    'capability' => false,
                    'description' => 'MPAA Rating',
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
                'content' => 'content_rating (string) - MPAA rating',
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

    /**
     * @return array
     */
    public function providerTestParseFailsOnInvalidTypes()
    {
        return [
            'type-unknown-representation' => [
                'content' => 'cast (\Unknown\Representation) - Cast'
            ],
            'type-unsupported-type' => [
                'content' => 'cast (ARRRRRRR) - Cast'
            ],
            'subtype-unknown-representation' => [
                'content' => 'cast (array<\Unknown\Representation>) - Cast'
            ],
            'subtype-with-non-array-type' => [
                'content' => 'cast (string<string>) - Cast'
            ]
        ];
    }
}
