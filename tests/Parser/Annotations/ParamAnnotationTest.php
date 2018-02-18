<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\InvalidMSONSyntaxException;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Parser\Annotations\CapabilityAnnotation;
use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Reader\Docblock;
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
        $docblock = new Docblock($content, __FILE__, 0, strlen($content));
        $annotation = new ParamAnnotation($this->application, $content, $docblock, $version);
        $annotation->process();
        $annotation->setVisibility($visible);
        $annotation->setDeprecated($deprecated);

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @ddataProvider providerAnnotation
     * @param string $content
     * @param Version|null $version
     * @param bool $visible
     * @param bool $deprecated
     * @param array $expected
     */
    /*public function testHydrate(
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
    }*/

    private function assertAnnotation(ParamAnnotation $annotation, array $expected): void
    {
        $this->assertTrue($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertTrue($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['field'], $annotation->getField());
        $this->assertSame($expected['type'], $annotation->getType());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertSame($expected['required'], $annotation->isRequired());
        $this->assertSame($expected['values'], $annotation->getValues());

        if (is_string($expected['capability'])) {
            $this->assertInstanceOf(CapabilityAnnotation::class, $annotation->getCapability());
        } else {
            $this->assertFalse($annotation->getCapability());
        }

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
                'content' => 'content_rating `G` (string, optional, nullable, MOVIE_RATINGS) - MPAA rating
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => true,
                    'required' => false,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => [
                        'G' => 'G rated',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated'
                    ],
                    'version' => false,
                    'visible' => true
                ]
            ],
            '_complete-with-markdown-description' => [
                'content' => 'content_rating `G` (string, optional, nullable, MOVIE_RATINGS) - This denotes the 
                    [MPAA rating](http://www.mpaa.org/film-ratings/) for the movie.
                    + Members
                        - `G` - G rated
                        - `PG` - PG rated
                        - `PG-13` - PG-13 rated',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'deprecated' => false,
                    'description' => 'This denotes the [MPAA rating](http://www.mpaa.org/film-ratings/) for the movie.',
                    'field' => 'content_rating',
                    'nullable' => true,
                    'required' => false,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => [
                        'G' => 'G rated',
                        'PG' => 'PG rated',
                        'PG-13' => 'PG-13 rated'
                    ],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'capability' => [
                'content' => 'content_rating `G` (string, REQUIRED, MOVIE_RATINGS) - MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => true,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => false,
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
                    'capability' => false,
                    'deprecated' => true,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => true,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => false,
                    'version' => false,
                    'visible' => true
                ]
            ],
            'enum-with-no-descriptions' => [
                'content' => 'is_kid_friendly `yes` (string, optional) - Is this movie kid friendly?
                    + Members
                        - `yes`
                        - `no`',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'Is this movie kid friendly?',
                    'field' => 'is_kid_friendly',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'yes',
                    'type' => 'string',
                    'values' => [
                        'no' => '',
                        'yes' => ''
                    ],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'enum-with-no-set-default' => [
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
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
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
                    ],
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
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => true,
                    'required' => true,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => false,
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
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => true,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => false,
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
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'The page number to show.',
                    'field' => 'page',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => false,
                    'type' => 'integer',
                    'values' => false,
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
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'Filter to apply to the results.',
                    'field' => 'filter',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => false,
                    'type' => 'string',
                    'values' => [
                        'embeddable' => 'Embeddable',
                        'playable' => 'Playable'
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
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => false,
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
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'Voluptate culpa ex, eiusmod rump sint id. Venison non ribeye landjaeger ' .
                        'laboris, enim jowl culpa meatloaf dolore mollit anim. Bacon shankle eiusmod hamburger enim. ' .
                        'Laboris lorem pastrami t-bone tempor ullamco swine commodo tri-tip in sirloin.',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => true,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => false,
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
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => false,
                    'version' => false,
                    'visible' => true
                ]
            ],
            'without-defined-requirement-but-capability' => [
                'content' => 'content_rating `G` (string, MOVIE_RATINGS) - MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => 'G',
                    'type' => 'string',
                    'values' => false,
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
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'nullable' => false,
                    'required' => false,
                    'sample_data' => false,
                    'type' => 'string',
                    'values' => false,
                    'version' => false,
                    'visible' => true
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'invalid-mson' => [
                'annotation' => ParamAnnotation::class,
                'content' => '',
                'expected.exception' => InvalidMSONSyntaxException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'param',
                    //'getDocblock' => '',
                    //'getValues' => []
                ]
            ],
            'unsupported-type' => [
                'annotation' => ParamAnnotation::class,
                'content' => 'content_rating `G` (str) - MPAA rating',
                'expected.exception' => UnsupportedTypeException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'content_rating `G` (str) - MPAA rating',
                    //'getDocblock' => null
                ]
            ]
        ];
    }
}
