<?php
namespace Mill\Exceptions\Version;

use Mill\Exceptions\ExceptionTrait;

trait VersionExceptionTrait
{
    use ExceptionTrait;

    /**
     * @var string|null
     */
    public $version = null;

    /**
     * @var string|null
     */
    public $proper_version = null;

    /**
     * Get the version that an annotation exception was triggered for.
     *
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the proper version that an annotation should be written as.
     *
     * @return string|null
     */
    public function getProperVersion()
    {
        return $this->proper_version;
    }
}
