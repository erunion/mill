<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Version;

class ParamAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param string $version
     * @param boolean $visible
     * @param boolean $deprecated
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, $version, $visible, $deprecated, array $expected)
    {
        $annotation = new ParamAnnotation($content, __CLASS__, __METHOD__, $version);
        $annotation->setVisibility($visible);
        $annotation->setDeprecated($deprecated);

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
            $this->assertInstanceOf(
                '\Mill\Parser\Annotations\CapabilityAnnotation',
                $annotation->getCapability()
            );
        } else {
            $this->assertFalse($annotation->getCapability());
        }

        if ($expected['version']) {
            $this->assertInstanceOf('Mill\Parser\Version', $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }

        $this->assertEmpty($annotation->getAliases());
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            '_complete' => [
                'content' => 'content_rating `G` (string, optional, MOVIE_RATINGS) - MPAA rating
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
                'content' => 'content_rating `G` (string, optional, MOVIE_RATINGS) - This denotes the 
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

    /**
     * @return array
     */
    public function providerAnnotationFailsOnInvalidContent()
    {
        return [
            'invalid-mson' => [
                'annotation' => '\Mill\Parser\Annotations\ParamAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\InvalidMSONSyntaxException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'param',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'unsupported-type' => [
                'annotation' => '\Mill\Parser\Annotations\ParamAnnotation',
                'content' => 'content_rating `G` (str) - MPAA rating',
                'expected.exception' => '\Mill\Exceptions\Annotations\UnsupportedTypeException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'content_rating `G` (str) - MPAA rating',
                    'getDocblock' => null
                ]
            ]
        ];
    }
}
