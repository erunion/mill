<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Annotations\VendorTagAnnotation;
use Mill\Parser\Version;

class ParamAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param bool $visible
     * @param bool $deprecated
     * @param array $expected
     */
    public function testAnnotation(
        string $content,
        ?Version $version,
        bool $visible,
        bool $deprecated,
        array $expected
    ): void {
        $annotation = new ParamAnnotation($content, __CLASS__, __METHOD__, $version);
        $annotation->process();
        $annotation->setVisibility($visible);
        $annotation->setDeprecated($deprecated);

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param bool $visible
     * @param bool $deprecated
     * @param array $expected
     */
    public function testHydrate(
        string $content,
        ?Version $version,
        bool $visible,
        bool $deprecated,
        array $expected
    ): void {
        $annotation = ParamAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ), $version);

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(ParamAnnotation $annotation, array $expected): void
    {
        $this->assertTrue($annotation->supportsDeprecation());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertTrue($annotation->supportsVendorTags());
        $this->assertTrue($annotation->requiresVisibilityDecorator());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['field'], $annotation->getField());
        $this->assertSame($expected['type'], $annotation->getType());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertSame($expected['required'], $annotation->isRequired());
        $this->assertSame($expected['values'], $annotation->getValues());

        $this->assertSame(
            $expected['vendor_tags'],
            array_map(
                function (VendorTagAnnotation $tag): string {
                    return $tag->getVendorTag();
                },
                $annotation->getVendorTags()
            )
        );

        if ($expected['version']) {
            $this->assertInstanceOf(Version::class, $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }

        $this->assertEmpty($annotation->getAliases());
    }

    public function providerAnnotation(): array
    {
        return [
            '_complete' => [
                'content' => 'content_rating `G` (enum, optional, nullable, tag:MOVIE_RATINGS) - MPAA rating
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
                    'description' => 'MPAA rating',
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
                    ],
                    'version' => false,
                    'visible' => true
                ]
            ],
            '_complete-with-markdown-description' => [
                'content' => 'content_rating `G` (enum, optional, nullable, tag:MOVIE_RATINGS) - This denotes the
                    [MPAA rating](http://www.mpaa.org/film-ratings/) for the movie.
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
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
                    ],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'deprecated' => [
                'content' => 'content_rating `G` (string, required) - MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => true,
                'expected' => [
                    'deprecated' => true,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => true,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'enum-with-no-descriptions' => [
                'content' => 'is_kid_friendly `yes` (enum, optional) - Is this movie kid friendly?
                    + Members
                        - `yes`
                        - `no`',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
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
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'enum-with-no-set-default' => [
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
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
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
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'nullable' => [
                'content' => 'content_rating `G` (string, required, nullable) - MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => true,
                    'required' => true,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'private' => [
                'content' => 'content_rating `G` (string, required) - MPAA rating',
                'version' => null,
                'visible' => false,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => true,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => false
                ]
            ],
            'tokens' => [
                'content' => '{page}',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
                    'description' => 'The page number to show.',
                    'field' => 'page',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => false,
                    'subtype' => false,
                    'type' => 'integer',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'tokens.acceptable_values' => [
                'content' => '{filter}
                    + Members
                        - `embeddable` - Embeddable
                        - `playable` - Playable',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
                    'description' => 'Filter to apply to the results.',
                    'field' => 'filter',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => false,
                    'subtype' => false,
                    'type' => 'enum',
                    'values' => [
                        'embeddable' => 'Embeddable',
                        'playable' => 'Playable'
                    ],
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'vendor-tag' => [
                'content' => 'content_rating `G` (string, REQUIRED, tag:MOVIE_RATINGS) - MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
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
                    ],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'versioned' => [
                'content' => 'content_rating `G` (string) - MPAA rating',
                'version' => new Version('1.1 - 1.2', __CLASS__, __METHOD__),
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => '1.1 - 1.2',
                    'visible' => true
                ]
            ],
            'with-a-long-description' => [
                'content' => 'content_rating `G` (string, required) - Voluptate culpa ex, eiusmod rump sint id. Venison
                    non ribeye landjaeger laboris, enim jowl culpa meatloaf dolore mollit anim. Bacon shankle eiusmod
                    hamburger enim. Laboris lorem pastrami t-bone tempor ullamco swine commodo tri-tip in sirloin.',
                'version' => null,
                'visible' => false,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
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
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => false
                ]
            ],
            'without-defined-requirement' => [
                'content' => 'content_rating `G` (string) - MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'without-defined-requirement-but-vendor-tag' => [
                'content' => 'content_rating `G` (string, tag:MOVIE_RATINGS) - MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
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
                    ],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'without-sample-data' => [
                'content' => 'content_rating (string) - MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => false,
                    'subtype' => false,
                    'type' => 'string',
                    'values' => [],
                    'vendor_tags' => [],
                    'version' => false,
                    'visible' => true
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'unsupported-type' => [
                'annotation' => ParamAnnotation::class,
                'content' => 'content_rating `G` (str) - MPAA rating',
                'expected.exception' => UnsupportedTypeException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'content_rating `G` (str) - MPAA rating',
                    'getDocblock' => null
                ]
            ],
            'restricted-field-name-is-detected' => [
                'annotation' => ParamAnnotation::class,
                'content' => '__NESTED_DATA__ (string) - MPAA rating',
                'expected.exception' => RestrictedFieldNameException::class,
                'expected.exception.asserts' => []
            ]
        ];
    }
}
