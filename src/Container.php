<?php
namespace Mill;

use Mill\Provider\Config;
use Mill\Provider\Filesystem;
use Mill\Provider\Reader;

class Container extends \Pimple\Container
{
    /**
     * Instantiate the container.
     *
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @codeCoverageIgnore
     * @param array $values The parameters or objects.
     */
    public function __construct(array $values = [])
    {
        if (!isset($values['config.path'])) {
            throw new \InvalidArgumentException('The Mill container must be passed a `config.path` value.');
        }

        if (!isset($values['config.load_bootstrap'])) {
            $values['config.load_bootstrap'] = true;
        }

        parent::__construct($values);

        $this->register(new Filesystem);
        $this->register(new Config);
        $this->register(new Reader);
    }

    /**
     * Return the current instance of the configuration system.
     *
     * @return \Mill\Config
     */
    public function getConfig(): \Mill\Config
    {
        return $this['config'];
    }

    /**
     * Return the current instance of the filesystem.
     *
     * @return \League\Flysystem\Filesystem
     */
    public function getFilesystem(): \League\Flysystem\Filesystem
    {
        return $this['filesystem'];
    }

    /**
     * Return the current instance of the annotation reader.
     *
     * @return \Closure
     */
    public function getAnnotationReader(): \Closure
    {
        return $this['reader.annotations'];
    }

    /**
     * Return the current instance of the annotation reader for representations.
     *
     * @return \Closure
     */
    public function getRepresentationAnnotationReader(): \Closure
    {
        return $this['reader.annotations.representation'];
    }
}
