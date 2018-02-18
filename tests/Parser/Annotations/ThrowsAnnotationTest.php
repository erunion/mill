<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\MissingRepresentationErrorCodeException;
use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Exceptions\Annotations\UncallableErrorCodeException;
use Mill\Exceptions\Annotations\UnknownErrorRepresentationException;
use Mill\Exceptions\Annotations\UnknownReturnCodeException;
use Mill\Parser\Annotations\CapabilityAnnotation;
use Mill\Parser\Annotations\ThrowsAnnotation;
use Mill\Parser\Version;

class ThrowsAnnotationTest extends AnnotationTest
{
    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param $version
     * @param bool $visible
     * @param array $expected
     */
    public function testAnnotation(string $content, $version, bool $visible, array $expected): void
    {
        $annotation = new ThrowsAnnotation($content, __CLASS__, __METHOD__, $version);
        $annotation->process();
        $annotation->setVisibility($visible);

        $this->assertAnnotation($annotation, $expected);
    }

    /**
     * @dataProvider providerAnnotation
     * @param string $content
     * @param $version
     * @param bool $visible
     * @param array $expected
     */
    public function testHydrate(string $content, $version, bool $visible, array $expected): void
    {
        $annotation = ThrowsAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ), $version);

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(ThrowsAnnotation $annotation, array $expected): void
    {
        $this->assertTrue($annotation->requiresVisibilityDecorator());
        $this->assertTrue($annotation->supportsVersioning());
        $this->assertFalse($annotation->supportsDeprecation());
        $this->assertFalse($annotation->supportsAliasing());

        $this->assertSame($expected, $annotation->toArray());
        $this->assertSame($expected['description'], $annotation->getDescription());
        $this->assertSame($expected['http_code'], $annotation->getHttpCode());
        $this->assertSame($expected['representation'], $annotation->getRepresentation());
        $this->assertSame($expected['error_code'], $annotation->getErrorCode());

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
            'bare' => [
                'content' => '(404, Error) If the movie could not be found.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie could not be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'capability' => [
                'content' => '(404, Error, BUY_TICKETS) If the movie could not be found.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie could not be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description' => [
                'content' => '(400, Error) If an unknown error occurred.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If an unknown error occurred.',
                    'error_code' => false,
                    'http_code' => '400 Bad Request',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description.has_parenthesis' => [
                'content' => '(403, Error) This is a description with a (parenthesis of something).',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'This is a description with a (parenthesis of something).',
                    'error_code' => false,
                    'http_code' => '403 Forbidden',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description.throw_type' => [
                'content' => '(404, Error) {movie}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description.throw_type.subthrow_type' => [
                'content' => '(404, Error) {movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found in the theater.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'error_code' => [
                'content' => '(403, Coded error, 666) If the user is not allowed to edit that movie.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the user is not allowed to edit that movie.',
                    'error_code' => '666',
                    'http_code' => '403 Forbidden',
                    'representation' => 'Coded error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'error_code.numerical' => [
                'content' => '(403, Coded error, 1337) If something cool happened.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If something cool happened.',
                    'error_code' => '1337',
                    'http_code' => '403 Forbidden',
                    'representation' => 'Coded error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'private' => [
                'content' => '(404, Error) {movie}',
                'version' => null,
                'visible' => false,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => false
                ]
            ],
            'versioned' => [
                'content' => '(404, Error) {movie}',
                'version' => new Version('1.1 - 1.2', __CLASS__, __METHOD__),
                'visible' => false,
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie cannot be found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => 'Error',
                    'version' => '1.1 - 1.2',
                    'visible' => false
                ]
            ],
            '_complete.description' => [
                'content' => '(404, Error, BUY_TICKETS) If the tickets URL does not exist.',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the tickets URL does not exist.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            '_complete.error_code' => [
                'content' => '(404, Coded error, 666, BUY_TICKETS) {movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie cannot be found in the theater.',
                    'error_code' => '666',
                    'http_code' => '404 Not Found',
                    'representation' => 'Coded error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            '_complete.type_subtype' => [
                'content' => '(404, Error, BUY_TICKETS) {movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie cannot be found in the theater.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => 'Error',
                    'version' => false,
                    'visible' => true
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'missing-http-code' => [
                'annotation' => ThrowsAnnotation::class,
                'content' => '',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'http_code',
                    'getAnnotation' => 'throws',
                    'getDocblock' => '',
                    'getValues' => []
                ]
            ],
            'missing-representation' => [
                'annotation' => ThrowsAnnotation::class,
                'content' => '{404} \Mill\Examples\Showtimes\Representations\Error',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'description',
                    'getAnnotation' => 'throws',
                    'getDocblock' => '{404} \Mill\Examples\Showtimes\Representations\Error',
                    'getValues' => []
                ]
            ],
            'missing-description' => [
                'annotation' => ThrowsAnnotation::class,
                'content' => '{404}',
                'expected.exception' => MissingRequiredFieldException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => 'representation',
                    'getAnnotation' => 'throws',
                    'getDocblock' => '{404}',
                    'getValues' => []
                ]
            ],
            'representation-is-unknown' => [
                'annotation' => ThrowsAnnotation::class,
                'content' => '{404} \UnknownRepresentation',
                'expected.exception' => UnknownErrorRepresentationException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '\UnknownRepresentation',
                    'getValues' => []
                ]
            ],
            /*'error-code-is-uncallable' => [
                'annotation' => ThrowsAnnotation::class,
                'content' => '{404} \Mill\Examples\Showtimes\Representations\CodedError (\Uncallable::CONSTANT)',
                'expected.exception' => UncallableErrorCodeException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '{404} \Mill\Examples\Showtimes\Representations\CodedError ' .
                        '(\Uncallable::CONSTANT)',
                    'getValues' => []
                ]
            ],*/
            'error-code-is-required-but-missing' => [
                'annotation' => ThrowsAnnotation::class,
                'content' => '{403} \Mill\Examples\Showtimes\Representations\CodedError',
                'expected.exception' => MissingRepresentationErrorCodeException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'getValues' => []
                ]
            ],
            'http-code-is-invalid' => [
                'annotation' => ThrowsAnnotation::class,
                'content' => '{440} \Mill\Examples\Showtimes\Representations\Error',
                'expected.exception' => UnknownReturnCodeException::class,
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
