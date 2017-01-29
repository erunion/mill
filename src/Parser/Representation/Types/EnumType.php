<?php
namespace Mill\Parser\Representation\Types;

use Mill\Parser\Representation\Type;

/**
 * Handler for the `enum` `@api-type` annotation.
 *
 */
class EnumType extends Type
{
    /**
     * @var bool
     */
    protected $allows_subtype = false;

    /**
     * @var bool
     */
    protected $requires_subtype = false;

    /**
     * @var bool
     */
    protected $requires_options = true;
}
