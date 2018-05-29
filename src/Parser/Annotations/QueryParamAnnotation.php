<?php
namespace Mill\Parser\Annotations;

/**
 * Handler for the `@api-queryparam` annotation.
 *
 */
class QueryParamAnnotation extends ParamAnnotation
{
    /**
     * @return string
     */
    public function getPayloadFormat(): string
    {
        return 'query';
    }
}
