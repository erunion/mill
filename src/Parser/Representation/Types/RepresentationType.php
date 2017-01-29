<?php
namespace Mill\Parser\Representation\Types;

use Mill\Parser\Representation\Type;

/**
 * Handler for the `representation` `@api-type` annotation.
 *
 */
class RepresentationType extends Type
{
    /**
     * @var bool
     */
    protected $allows_subtype = true;

    /**
     * @var bool
     */
    protected $requires_subtype = true;

    /**
     * @var bool
     */
    protected $requires_options = false;
}
