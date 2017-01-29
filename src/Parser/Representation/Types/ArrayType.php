<?php
namespace Mill\Parser\Representation\Types;

use Mill\Parser\Representation\Type;

/**
 * Handler for the `array` `@api-type` annotation.
 *
 */
class ArrayType extends Type
{
    /**
     * @var bool
     */
    protected $allows_subtype = true;

    /**
     * @var bool
     */
    protected $requires_subtype = false;

    /**
     * @var bool
     */
    protected $requires_options = false;
}
