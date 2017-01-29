<?php
namespace Mill\Parser\Representation;

/**
 * Base class for supported representation field `@api-type` types.
 *
 */
abstract class Type
{
    /**
     * Does this type allow a subtype?
     *
     * @var bool
     */
    protected $allows_subtype = true;

    /**
     * Does this type require a subtype definition?
     *
     * @var bool
     */
    protected $requires_subtype = false;

    /**
     * Does this type require options?
     *
     * @var bool
     */
    protected $requires_options = false;

    /**
     * Does this type require a subtype definition?
     *
     * @return bool
     */
    public function allowsSubtype()
    {
        return $this->allows_subtype;
    }

    /**
     * Does this type require a subtype definition?
     *
     * @return bool
     */
    public function requiresSubtype()
    {
        return $this->requires_subtype;
    }

    /**
     * Does this type allow options?
     *
     * @return bool
     */
    public function requiresOptions()
    {
        return $this->requires_options;
    }
}
