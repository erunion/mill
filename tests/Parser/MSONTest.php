<?php
namespace Mill\Tests\Parser;

use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\MSON\ImproperlyWrittenEnumException;
use Mill\Exceptions\MSON\MissingOptionsException;
use Mill\Exceptions\MSON\MissingSubtypeException;
use Mill\Parser\MSON;
use Mill\Tests\TestCase;

class MSONTest extends TestCase
{
    /**
     * @dataProvider providerTestParse
     * @param string $content
     * @param array $expected
     */
    public function testParse(string $content, array $expected): void
    {
        $mson = (new MSON(__CLASS__, __METHOD__, $this->getConfig()))->parse($content);
        $this->assertSame($expected, $mson->toArray());
    }

    public function testEnumFailsWithoutValues(): void
    {
        $this->expectException(MissingOptionsException::class);

        $content = 'content_rating (enum) - MPAA rating';
        (new MSON(__CLASS__, __METHOD__, $this->getConfig()))->parse($content);
    }

    public function testEnumFailsWhenWrittenAsAString(): void
    {
        $this->expectException(ImproperlyWrittenEnumException::class);

        $content = 'content_rating `G` (string, optional) - MPAA rating
            + Members
                - `G` - G rated
                - `PG` - PG rated
                - `PG-13` - PG-13 rated';

        (new MSON(__CLASS__, __METHOD__, $this->getConfig()))->parse($content);
    }

    public function testParseFailsOnArrayTypeWithoutASubtype(): void
    {
        $this->expectException(MissingSubtypeException::class);

        $content = 'websites (array) - A list of websites.';
        (new MSON(__CLASS__, __METHOD__, $this->getConfig()))->parse($content);
    }

    /**
     * @dataProvider providerTestParseFailsOnInvalidTypes
     * @param string $content
     */
    public function testParseFailsOnInvalidTypes(string $content): void
    {
        $this->expectException(UnsupportedTypeException::class);
        (new MSON(__CLASS__, __METHOD__, $this->getConfig()))->parse($content);
    }

    public function providerTestParse(): array
    {
        return [
            '_complete' => [
                'content' => 'content_rating `G` (enum, optional, tag:MOVIE_RATINGS, needs:validUser) - MPAA rating
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'expected' => [
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'G' => 'G rated',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated'
                    ],
                    'vendor_tags' => [
                        'tag:MOVIE_RATINGS',
                        'needs:validUser'
                    ]
                ]
            ],
            'vendor-tag' => [
                'content' => 'content_rating `G` (string, REQUIRED, tag:MOVIE_RATINGS) - MPAA rating',
                'expected' => [
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => true,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [
                        'tag:MOVIE_RATINGS'
                    ]
                ]
            ],
            'description-long' => [
                'content' => 'content_rating `G` (string, required) - Voluptate culpa ex, eiusmod rump sint id. Venison
                    non ribeye landjaeger laboris, enim jowl culpa meatloaf dolore mollit anim. Bacon shankle eiusmod
                    hamburger enim. Laboris lorem pastrami t-bone tempor ullamco swine commodo tri-tip in sirloin.',
                'expected' => [
                    'description' => 'Voluptate culpa ex, eiusmod rump sint id. Venison non ribeye landjaeger ' .
                        'laboris, enim jowl culpa meatloaf dolore mollit anim. Bacon shankle eiusmod hamburger enim. ' .
                        'Laboris lorem pastrami t-bone tempor ullamco swine commodo tri-tip in sirloin.',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => true,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => []
                ]
            ],
            'description-markdown' => [
                'content' => 'content_rating `G` (enum, optional, nullable, tag:MOVIE_RATINGS) - This denotes the
                    [MPAA rating](http://www.mpaa.org/film-ratings/) for the movie.
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'expected' => [
                    'description' => 'This denotes the [MPAA rating](http://www.mpaa.org/film-ratings/) for the movie.',
                    'field' => 'content_rating',
                    'nullable' => true,
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'G' => 'G rated',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated'
                    ],
                    'vendor_tags' => [
                        'tag:MOVIE_RATINGS'
                    ]
                ]
            ],
            'description-starts-on-new-line' => [
                'content' => 'content_rating `G` (enum)
                    - MPAA Rating
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'expected' => [
                    'description' => 'MPAA Rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'G' => 'G rated',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated'
                    ],
                    'vendor_tags' => []
                ]
            ],
            'enum-with-extra-long-descriptions' => [
                'content' => 'access_type `default` (enum) - The promotion access type, which is a purchase option
                    that is not available on the container. VIP promotions always make the content free of charge. If
                    you use this type, you must further define the promotion with the `download` or `stream_period`
                    fields.
                    + Members
                        - `default` - Promotions grant discount on the existing purchase options for an On Demand 
                            Container.
                        - `optional`
                        - `vip` - Promotions can be used to grant free access to vod content before it is released, or 
                            to offer a purchase option that is not available on the container. "vip" promotions will 
                            always make the content free, and must be further defined with the `download` or 
                            `stream_period` fields.',
                'expected' => [
                    'description' => 'The promotion access type, which is a purchase option that is not available on ' .
                        'the container. VIP promotions always make the content free of charge. If you use this ' .
                        'type, you must further define the promotion with the `download` or `stream_period` fields.',
                    'field' => 'access_type',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'default',
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'default' => 'Promotions grant discount on the existing purchase options for an On Demand ' .
                            'Container.',
                        'optional' => '',
                        'vip' => 'Promotions can be used to grant free access to vod content before it is released, ' .
                            'or to offer a purchase option that is not available on the container. "vip" promotions ' .
                            'will always make the content free, and must be further defined with the `download` or ' .
                            '`stream_period` fields.',
                    ],
                    'vendor_tags' => []
                ]
            ],
            'enum-without-descriptions' => [
                'content' => 'is_kid_friendly `yes` (enum, optional) - Is this movie kid friendly?
                    + Members
                        - `yes`
                        - `no`',
                'expected' => [
                    'description' => 'Is this movie kid friendly?',
                    'field' => 'is_kid_friendly',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'yes',
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'no' => '',
                        'yes' => ''
                    ],
                    'vendor_tags' => []
                ]
            ],
            'enum-without-set-default' => [
                'content' => 'content_rating `G` (enum, optional) - MPAA rating
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
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'G' => 'G rated',
                        'NC-17' => 'NC-17 rated',
                        'NR' => 'No rating',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated',
                        'R' => 'R rated',
                        'UR' => 'Unrated',
                        'X' => 'X-rated'
                    ],
                    'vendor_tags' => []
                ]
            ],
            'field-dot-notation' => [
                'content' => 'content.rating `G` (string) - MPAA rating',
                'expected' => [
                    'description' => 'MPAA rating',
                    'field' => 'content.rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => []
                ]
            ],
            'type-array-with-subtype-object' => [
                'content' => 'websites (array<object>) - The users\' list of websites.',
                'expected' => [
                    'description' => 'The users\' list of websites.',
                    'field' => 'websites',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => '',
                    'subtype' => 'object',
                    'type' => 'array',
                    'values' => [],
                    'vendor_tags' => []
                ]
            ],
            'type-array-with-subtype-representation' => [
                'content' => 'cast (array<\Mill\Examples\Showtimes\Representations\Person>) - Cast members',
                'expected' => [
                    'description' => 'Cast members',
                    'field' => 'cast',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => '',
                    'subtype' => '\Mill\Examples\Showtimes\Representations\Person',
                    'type' => 'array',
                    'values' => [],
                    'vendor_tags' => []
                ]
            ],
            'type-representation' => [
                'content' => 'director (\Mill\Examples\Showtimes\Representations\Person) - Director',
                'expected' => [
                    'description' => 'Director',
                    'field' => 'director',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => '',
                    'subtype' => false,
                    'type' => '\Mill\Examples\Showtimes\Representations\Person',
                    'values' => [],
                    'vendor_tags' => []
                ]
            ],
            'without-defined-requirement-but-vendor-tags' => [
                'content' => 'content_rating `G` (string, tag:MOVIE_RATINGS) - MPAA rating',
                'expected' => [
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [
                        'tag:MOVIE_RATINGS'
                    ]
                ]
            ],
            'without-sample-data' => [
                'content' => 'content_rating (string) - MPAA rating',
                'expected' => [
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => '',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => []
                ]
            ]
        ];
    }

    public function providerTestParseFailsOnInvalidTypes(): array
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
