<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Version;

class ParamAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($param, $version, $visible, $deprecated, $expected)
    {
        $annotation = new ParamAnnotation($param, __CLASS__, __METHOD__, $version);
        $annotation->setVisibility($visible);
        $annotation->setDeprecated($deprecated);

        $this->assertTrue($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertTrue($annotation->supportsDeprecation());

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
    }

    /**
     * @return array
     */
    public function providerAnnotation()
    {
        return [
            '_complete' => [
                'param' => 'content_rating `G` (string, optional, MOVIE_RATINGS) - MPAA rating
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
                'param' => 'content_rating `G` (string, optional, MOVIE_RATINGS) - This denotes the 
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
                'param' => 'content_rating `G` (string, REQUIRED, MOVIE_RATINGS) - MPAA rating',
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
                'param' => 'content_rating `G` (string, required) - MPAA rating',
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
                'param' => 'is_kid_friendly `yes` (string, optional) - Is this movie kid friendly?
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
                'param' => 'content_rating `G` (string, optional) - MPAA rating
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
                'param' => 'content_rating `G` (string, required) - MPAA rating',
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
                'param' => '{page}',
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
                'param' => '{filter}
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
                'param' => 'content_rating `G` (string) - MPAA rating',
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
                'param' => 'content_rating `G` (string, required) - Voluptate culpa ex, eiusmod rump sint id. Venison 
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
                'param' => 'content_rating `G` (string) - MPAA rating',
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
                'param' => 'content_rating `G` (string, MOVIE_RATINGS) - MPAA rating',
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
                'param' => 'content_rating (string) - MPAA rating',
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
    public function providerAnnotationFailsOnInvalidAnnotations()
    {
        return [
            'invalid-mson' => [
                'annotation' => '\Mill\Parser\Annotations\ParamAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\InvalidMSONSyntaxException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'param',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'unsupported-type' => [
                'annotation' => '\Mill\Parser\Annotations\ParamAnnotation',
                'docblock' => 'content_rating `G` (str) - MPAA rating',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\UnsupportedTypeException',
                'expected.exception.asserts' => [
                    'getAnnotation' => 'content_rating `G` (str) - MPAA rating',
                    'getDocblock' => null
                ]
            ]
        ];
    }
}
