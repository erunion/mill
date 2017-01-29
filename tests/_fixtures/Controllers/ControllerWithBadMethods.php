<?php
namespace Mill\Tests\Fixtures\Controllers;

class ControllerWithBadMethods
{
    /*
     * Test throwing an exception when no annotations could be parsed because of a bad docblock.
     *
     * @api-label Test method
     */
    public function withNoParsedAnnotations()
    {
        //
    }

    /**
     * Test throwing an exception when a required `@api-label` annotation is missing.
     *
     * @api-uri {Something} /some/page
     */
    public function withMissingRequiredLabelAnnotation()
    {
        //
    }

    /**
     * Test throwing an exception when multiple `@api-label` annotations are present.
     *
     * @api-label Test method
     * @api-label Test method
     */
    public function withMultipleLabelAnnotations()
    {
        //
    }

    /**
     * Test throwing an exception when a required `@api-contentType` annotation is missing.
     *
     * @api-label Test Method
     * @api-uri {Something} /some/page
     */
    public function withMissingRequiredContentTypeAnnotation()
    {
        //
    }

    /**
     * Test throwing an exception when multiple `@api-contentType` annotations are present.
     *
     * @api-label Test method
     * @api-uri {Something} /some/page
     * @api-contentType application/json
     * @api-contentType text/xml
     */
    public function withMultipleContentTypeAnnotations()
    {
        //
    }

    /**
     * Test throwing an exception when a required `@api-uri` annotation is missing.
     *
     * @api-label Test method
     * @api-contentType application/json
     * @api-param:public {page}
     */
    public function withRequiredUriAnnotationMissing()
    {
        //
    }

    /**
     * Test throwing an exception when a required visibility decorator is missing on an annotation.
     *
     * @api-label Test method
     * @api-uri {Root} /
     * @api-contentType application/json
     * @api-return:public {collection} \Mill\Examples\Showtimes\Representations\Representation
     */
    public function withMissingRequiredVisibilityDecorator()
    {
        //
    }

    /**
     * Test throwing an exception when an unsupported decorator is found.
     *
     * @api-label Test method
     * @api-uri:special {Root} /
     * @api-contentType application/json
     * @api-return {collection} \Mill\Examples\Showtimes\Representations\Representation
     */
    public function withUnsupportedDecorator()
    {
        //
    }

    /**
     * Test throwing an exception when there are private annotations on a private action.
     *
     * @api-label Test method
     * @api-uri:private {Search} /search
     * @api-contentType application/json
     * @api-scope public
     * @api-return:private {collection} \Mill\Examples\Showtimes\Representations\Representation
     * @api-throws:public {403} \Mill\Examples\Showtimes\Representations\CodedError
     *      (Mill\Examples\Showtimes\Representations\CodedError::DISALLOWED) If the user isn't allowed to do
     *      something.
     */
    public function withPublicAnnotationsOnAPrivateAction()
    {
        //
    }
}
