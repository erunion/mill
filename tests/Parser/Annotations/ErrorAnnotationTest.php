<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\InvalidMSONSyntaxException;
use Mill\Exceptions\Annotations\MissingRepresentationErrorCodeException;
//use Mill\Exceptions\Annotations\MissingRequiredFieldException;
use Mill\Exceptions\Annotations\UnknownErrorRepresentationException;
use Mill\Exceptions\Annotations\UnknownReturnCodeException;
use Mill\Parser\Annotations\CapabilityAnnotation;
use Mill\Parser\Annotations\ErrorAnnotation;
use Mill\Parser\Version;

class ErrorAnnotationTest extends AnnotationTest
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
        $annotation = new ErrorAnnotation($content, __CLASS__, __METHOD__, $version);
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
        /** @var ErrorAnnotation $annotation */
        $annotation = ErrorAnnotation::hydrate(array_merge(
            $expected,
            [
                'class' => __CLASS__,
                'method' => __METHOD__
            ]
        ), $version);

        $this->assertAnnotation($annotation, $expected);
    }

    private function assertAnnotation(ErrorAnnotation $annotation, array $expected): void
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
                'content' => '404 (\Mill\Examples\Showtimes\Representations\Error) - If the movie could not be found.',
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
                'content' => '404 (\Mill\Examples\Showtimes\Representations\Error, BUY_TICKETS) - If the movie ' .
                    'could not be found.',
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
                'content' => '400 (\Mill\Examples\Showtimes\Representations\Error) - If an unknown error occurred.',
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
                'content' => '403 (\Mill\Examples\Showtimes\Representations\Error) - This is a description with a ' .
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
            'description.error_type' => [
                'content' => '404 (\Mill\Examples\Showtimes\Representations\Error) - {movie}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If movie was not found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'description.error_type.suberror_type' => [
                'content' => '404 (\Mill\Examples\Showtimes\Representations\Error) - {movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => false,
                    'description' => 'If movie was not found in the theater.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ],
            'error_code' => [
                'content' => '403 (\Mill\Examples\Showtimes\Representations\CodedError<666>) - If the user is not ' .
                    'allowed to edit that movie.',
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
                'content' => '403 (\Mill\Examples\Showtimes\Representations\CodedError<1337>) - If something cool ' .
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
                'content' => '404 (\Mill\Examples\Showtimes\Representations\Error) - {movie}',
                'version' => null,
                'visible' => false,
                'expected' => [
                    'capability' => false,
                    'description' => 'If movie was not found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => false
                ]
            ],
            'versioned' => [
                'content' => '404 (\Mill\Examples\Showtimes\Representations\Error) - {movie}',
                'version' => new Version('1.1 - 1.2', __CLASS__, __METHOD__),
                'visible' => false,
                'expected' => [
                    'capability' => false,
                    'description' => 'If movie was not found.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => '1.1 - 1.2',
                    'visible' => false
                ]
            ],
            '_complete.description' => [
                'content' => '404 (\Mill\Examples\Showtimes\Representations\Error, BUY_TICKETS) - If the tickets ' .
                    'URL does not exist.',
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
                'content' => '404 (\Mill\Examples\Showtimes\Representations\CodedError<666>, BUY_TICKETS) - ' .
                    '{movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If movie was not found in the theater.',
                    'error_code' => '666',
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'version' => false,
                    'visible' => true
                ]
            ],
            '_complete.type_subtype' => [
                'content' => '404 (\Mill\Examples\Showtimes\Representations\Error, BUY_TICKETS) - {movie,theater}',
                'version' => null,
                'visible' => true,
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If movie was not found in the theater.',
                    'error_code' => false,
                    'http_code' => '404 Not Found',
                    'representation' => '\Mill\Examples\Showtimes\Representations\Error',
                    'version' => false,
                    'visible' => true
                ]
            ]
        ];
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'representation-is-unknown' => [
                'annotation' => ErrorAnnotation::class,
                'content' => '404 (\UnknownRepresentation) - For some reason.',
                'expected.exception' => UnknownErrorRepresentationException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '404 (\UnknownRepresentation) - For some reason.',
                    'getValues' => []
                ]
            ],
            'error-code-is-required-but-missing' => [
                'annotation' => ErrorAnnotation::class,
                'content' => '403 (\Mill\Examples\Showtimes\Representations\CodedError) - For some reason.',
                'expected.exception' => MissingRepresentationErrorCodeException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '\Mill\Examples\Showtimes\Representations\CodedError',
                    'getValues' => []
                ]
            ],
            'http-code-is-invalid' => [
                'annotation' => ErrorAnnotation::class,
                'content' => '440 (\Mill\Examples\Showtimes\Representations\Error) - For some reason.',
                'expected.exception' => UnknownReturnCodeException::class,
                'expected.exception.asserts' => [
                    'getRequiredField' => null,
                    'getAnnotation' => null,
                    'getDocblock' => '440 (\Mill\Examples\Showtimes\Representations\Error) - For some reason.',
                    'getValues' => []
                ]
            ]
        ];
    }
}
