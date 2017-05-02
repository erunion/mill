<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Version;

class ThrowsAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param string $version
     * @param boolean $visible
     * @param array $expected
     * @return void
     */
    public function testAnnotation($content, $version, $visible, array $expected)
    {
        $annotation = new ThrowsAnnotation($content, __CLASS__, __METHOD__, $version);
        $annotation->setVisibility($visible);

        $this->assertTrue($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertSame($expected['http_code'], $annotation->getHttpCode());
        $this->assertSame($expected['representation'], $annotation->getRepresentation());
        $this->assertSame($expected['error_code'], $annotation->getErrorCode());

        if (is_string($expected['capability'])) {
            $this->assertInstanceOf(
                '\Mill\Parser\Annotations\CapabilityAnnotation',
                $annotation->getCapability()
            );
        } else {
            $this->assertFalse($annotation->getCapability());
        }

        if ($expected['version']) {
            $this->assertInstanceOf('\Mill\Parser\Version', $annotation->getVersion());
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
            'bare' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error If the movie could not be found.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie could not be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'capability' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error +BUY_TICKETS+ If the movie could ' .
                    'not be found.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie could not be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description' => [
                'content' => '{400} \Mill\Examples\Showtimes\Representations\Error If an unknown error occurred.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If an unknown error occurred.',
                    'error_code' => false,
                    'http_code' => '400 Bad Request',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description.has_parenthesis' => [
                'content' => '{403} \Mill\Examples\Showtimes\Representations\Error This is a description with a ' .
                    '(parenthesis of something).',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'This is a description with a (parenthesis of something).',
                    'error_code' => false,
                    'http_code' => '403 Forbidden',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description.throw_type' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error {movie}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description.throw_type.subthrow_type' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error {movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found in the theater.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'error_code' => [
                'content' => '{403} \Mill\Examples\Showtimes\Representations\CodedError ' .
                    '(Mill\Examples\Showtimes\Representations\CodedError::DISALLOWED) If the user is not allowed to ' .
                    'edit that movie.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the user is not allowed to edit that movie.',
                    'error_code' => '666',
                    'http_code' => '403 Forbidden',
                    'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'error_code.numerical' => [
                'content' => '{403} \Mill\Examples\Showtimes\Representations\CodedError (1337) If something cool ' .
                    'happened.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If something cool happened.',
                    'error_code' => '1337',
                    'http_code' => '403 Forbidden',
                    'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'private' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error {movie}',
                'version' => null,
                'visible' => false,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => false
                ]
            ],
            'versioned' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error {movie}',
                'version' => new Version('1.1 - 1.2', __CLASS__, __METHOD__),
                'visible' => false,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => '1.1 - 1.2',
                    'visible' => false
                ]
            ],
            '_complete.description' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error +BUY_TICKETS+ ' .
                    'If the tickets URL does not exist.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the tickets URL does not exist.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            '_complete.error_code' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\CodedError ' .
                    '(\Mill\Examples\Showtimes\Representations\CodedError::DISALLOWED) +BUY_TICKETS+ ' .
                    '{movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie cannot be found in the theater.',
                    'error_code' => '666',
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'version' => false,
                    'visible' => true
                ]
            ],
            '_complete.type_subtype' => [
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error +BUY_TICKETS+ ' .
                    '{movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie cannot be found in the theater.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
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
            'missing-http-code' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'content' => '',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'http_code',
                    'getAnnotation' => 'throws',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'missing-representation' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'description',
                    'getAnnotation' => 'throws',
                    'getDocblock' => '{404} \Mill\Examples\Showtimes\Representations\Error',
                    'getValues' => []
                ]
            ],
            'missing-description' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'content' => '{404}',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRequiredFieldException',
                'expected.exception.asserts' => [
                    'getRequiredField' => 'representation',
                    'getAnnotation' => 'throws',
                    'getDocblock' => '{404}',
                    'getValues' => []
                ]
            ],
            'representation-is-unknown' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'content' => '{404} \UnknownRepresentation',
                'expected.exception' =>  '\Mill\Exceptions\Annotations\UnknownErrorRepresentationException',
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '\UnknownRepresentation',
                    'getValues' => []
                ]
            ],
            'error-code-is-uncallable' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'content' => '{404} \Mill\Examples\Showtimes\Representations\CodedError (\Uncallable::CONSTANT)',
                'expected.exception' => '\Mill\Exceptions\Annotations\UncallableErrorCodeException',
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '{404} \Mill\Examples\Showtimes\Representations\CodedError ' .
                        '(\Uncallable::CONSTANT)',
                    'getValues' => []
                ]
            ],
            'error-code-is-required-but-missing' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'content' => '{403} \Mill\Examples\Showtimes\Representations\CodedError',
                'expected.exception' => '\Mill\Exceptions\Annotations\MissingRepresentationErrorCodeException',
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'getValues' => []
                ]
            ],
            'http-code-is-invalid' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'content' => '{440} \Mill\Examples\Showtimes\Representations\Error',
                'expected.exception' => '\Mill\Exceptions\Annotations\UnknownReturnCodeException',
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '{440} \Mill\Examples\Showtimes\Representations\Error',
                    'getValues' => []
                ]
            ]
        ];
    }
}
