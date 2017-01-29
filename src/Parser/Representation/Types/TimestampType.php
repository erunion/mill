<?php
namespace Mill\Parser\Representation\Types;

use Mill\Parser\Representation\Type;

/**
 * Handler for the `timestamp` `@api-type` annotation.
 *
 */
class TimestampType extends Type
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
    protected $requires_options = false;
}
