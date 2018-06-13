<?php
namespace Mill\Tests\Parser\Annotations;

use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Representation\RestrictedFieldNameException;
use Mill\Parser\Annotations\QueryParamAnnotation;
use Mill\Parser\Version;

class QueryParamAnnotationTest extends ParamAnnotationTest
{
    const PAYLOAD_FORMAT = 'query';

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
        $annotation = new QueryParamAnnotation($content, __CLASS__, __METHOD__, $version);
        $annotation->process();
        $annotation->setVisibility($visible);
        $annotation->setDeprecated($deprecated);

        $this->assertAnnotation($annotation, $expected);
    }

    public function providerAnnotationFailsOnInvalidContent(): array
    {
        return [
            'unsupported-type' => [
                'annotation' => QueryParamAnnotation::class,
                'content' => 'content_rating `G` (str) - MPAA rating',
                'expected.exception' => UnsupportedTypeException::class,
                'expected.exception.asserts' => [
                    'getAnnotation' => 'content_rating `G` (str) - MPAA rating',
                    'getDocblock' => null
                ]
            ],
            'restricted-field-name-is-detected' => [
                'annotation' => QueryParamAnnotation::class,
                'content' => '__NESTED_DATA__ (string) - MPAA rating',
                'expected.exception' => RestrictedFieldNameException::class,
                'expected.exception.asserts' => []
            ]
        ];
    }
}
