<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ParamAnnotation;
use Mill\Parser\Version;

class ParamAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider annotationProvider
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

        if (is_array($expected['version'])) {
            $this->assertInstanceOf('Mill\Parser\Version', $annotation->getVersion());
        } else {
            $this->assertFalse($annotation->getVersion());
        }
    }

    /**
     * @return array
     */
    public function annotationProvider()
    {
        return [
            'capability' => [
                'param' => '{string} content_rating +MOVIE_RATINGS+ MPAA rating',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => 'MOVIE_RATINGS',
                    'deprecated' => false,
                    'description' => 'MPAA rating',
                    'field' => 'content_rating',
                    'required' => true,
                    'type' => 'string',
                    'values' => false,
                    'version' => false,
                    'visible' => true
                ]
            ],
            'deprecated' => [
                'param' => '{page}',
                'version' => null,
                'visible' => false,
                'deprecated' => true,
                'expected' => [
                    'capability' => false,
                    'deprecated' => true,
                    'description' => 'The page number to show.',
                    'field' => 'page',
                    'required' => false,
                    'type' => 'int',
                    'values' => false,
                    'version' => false,
                    'visible' => false
                ]
            ],
            'private' => [
                'param' => '{string} __testing [true|false] Because reasons',
                'version' => null,
                'visible' => false,
                'deprecated' => false,
                'expected' => [
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'Because reasons',
                    'field' => '__testing',
                    'required' => true,
                    'type' => 'string',
                    'values' => [
                        'true',
                        'false'
                    ],
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
                    'type' => 'int',
                    'values' => false,
                    'version' => false,
                    'visible' => true
                ]
            ],
            'tokens.acceptable_values' => [
                'param' => '{filter} [embeddable|playable]',
                'version' => null,
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'Filter to apply to the results.',
                    'field' => 'filter',
                    'required' => false,
                    'type' => 'string',
                    'values' => [
                        'embeddable',
                        'playable'
                    ],
                    'version' => false,
                    'visible' => true
                ]
            ],
            'versioned' => [
                'param' => '{page}',
                'version' => new Version('1.1 - 1.2', __CLASS__, __METHOD__),
                'visible' => true,
                'deprecated' => false,
                'expected' => [
                    'capability' => false,
                    'deprecated' => false,
                    'description' => 'The page number to show.',
                    'field' => 'page',
                    'required' => false,
                    'type' => 'int',
                    'values' => false,
                    'version' => [
                        'start' => '1.1',
                        'end' => '1.2'
                    ],
                    'visible' => true
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function badAnnotationProvider()
    {
        return [
            'missing-field-name' => [
                'annotation' => '\Mill\Parser\Annotations\ParamAnnotation',
                'docblock' => '{string}',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`field`/'
                ]
            ],
            'missing-type' => [
                'annotation' => '\Mill\Parser\Annotations\ParamAnnotation',
                'docblock' => '__testing',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`type`/'
                ]
            ],
            'values-are-in-the-wrong-format' => [
                'annotation' => '\Mill\Parser\Annotations\ParamAnnotation',
                'docblock' => '{string} __testing [true,false] Because reasons',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\BadOptionsListException',
                'expected.exception.regex' => [
                    '/true,false/'
                ]
            ],
            'missing-description' => [
                'annotation' => '\Mill\Parser\Annotations\ParamAnnotation',
                'docblock' => '{string} __testing [true|false]',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`description`/'
                ]
            ]
        ];
    }
}
