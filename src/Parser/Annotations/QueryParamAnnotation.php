<?php
namespace Mill\Parser\Annotations;

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
