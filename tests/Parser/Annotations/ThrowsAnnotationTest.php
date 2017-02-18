<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Version;

class ThrowsAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     */
    public function testAnnotation($param, $version, $visible, $expected)
    {
        $annotation = new ThrowsAnnotation($param, __CLASS__, __METHOD__, $version);
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
                'param' => '{404} \Mill\Examples\Showtimes\Representations\Error',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => false,
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'capability' => [
                'param' => '{404} \Mill\Examples\Showtimes\Representations\Error +BUY_TICKETS+',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => false,
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description' => [
                'param' => '{400} \Mill\Examples\Showtimes\Representations\Error If an unknown error occurred.',
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
                '{403} \Mill\Examples\Showtimes\Representations\Error This is a description with a ' .
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
                'param' => '{404} \Mill\Examples\Showtimes\Representations\Error {movie}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description.throw_type.subthrow_type' => [
                'param' => '{404} \Mill\Examples\Showtimes\Representations\Error {movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found in the theater',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'error_code' => [
                'param' => '{403} \Mill\Examples\Showtimes\Representations\CodedError ' .
                    '(Mill\Examples\Showtimes\Representations\CodedError::DISALLOWED)',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => false,
                    'error_code' => '666',
                    'http_code' => '403 Forbidden',
                    'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'error_code.numerical' => [
                'param' => '{403} \Mill\Examples\Showtimes\Representations\CodedError (1337)',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => false,
                    'error_code' => '1337',
                    'http_code' => '403 Forbidden',
                    'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'private' => [
                'param' => '{404} \Mill\Examples\Showtimes\Representations\Error',
                'version' => null,
                'visible' => false,
                'expected' => [
                    'capability' => false,
                    'description' => false,
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => false
                ]
            ],
            'versioned' => [
                'param' => '{404} \Mill\Examples\Showtimes\Representations\Error',
                'version' => new Version('1.1 - 1.2', __CLASS__, __METHOD__),
                'visible' => false,
                'expected' => [
                    'capability' => false,
                    'description' => false,
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => '1.1 - 1.2',
                    'visible' => false
                ]
            ],
            '_complete.description' => [
                'param' => '{404} \Mill\Examples\Showtimes\Representations\Error +BUY_TICKETS+ ' .
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
                'param' => '{404} \Mill\Examples\Showtimes\Representations\CodedError ' .
                    '(\Mill\Examples\Showtimes\Representations\CodedError::DISALLOWED) +BUY_TICKETS+ ' .
                    '{movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie cannot be found in the theater',
                    'error_code' => '666',
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'version' => false,
                    'visible' => true
                ]
            ],
            '_complete.type_subtype' => [
                'param' => '{404} \Mill\Examples\Showtimes\Representations\Error +BUY_TICKETS+ ' .
                    '{movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie cannot be found in the theater',
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
    public function providerAnnotationFailsOnInvalidAnnotations()
    {
        return [
            'missing-http-code' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'docblock' => '',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`http_code`/'
                ]
            ],
            'missing-representation' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'docblock' => '{404}',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\MissingRequiredFieldException',
                'expected.exception.regex' => [
                    '/`representation`/'
                ]
            ],
            'representation-is-unknown' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'docblock' => '{404} \UnknownRepresentation',
                'expected.exception' =>
                    '\Mill\Exceptions\Resource\Annotations\UnknownErrorRepresentationException',
                'expected.exception.regex' => []
            ],
            'error-code-is-uncallable' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'docblock' => '{404} \Mill\Examples\Showtimes\Representations\CodedError (\Uncallable::CONSTANT)',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\UncallableErrorCodeException',
                'expected.exception.regex' => [
                    '/uncallable/'
                ]
            ],
            'error-code-is-required-but-missing' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'docblock' => '{403} \Mill\Examples\Showtimes\Representations\CodedError',
                'expected.exception' =>
                    '\Mill\Exceptions\Resource\Annotations\MissingRepresentationErrorCodeException',
                'expected.exception.regex' => []
            ],
            'http-code-is-invalid' => [
                'annotation' => '\Mill\Parser\Annotations\ThrowsAnnotation',
                'docblock' => '{440} \Mill\Examples\Showtimes\Representations\Error',
                'expected.exception' => '\Mill\Exceptions\Resource\Annotations\UnknownReturnCodeException',
                'expected.exception.regex' => [
                    '/code/'
                ]
            ]
        ];
    }
}
