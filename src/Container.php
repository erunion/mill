<?php
namespace Mill;

use Mill\Provider\Config;
use Mill\Provider\Filesystem;
use Mill\Provider\Reader;

class Container extends \Pimple\Container
{
    /**
     * @var Container|null
     */
    protected static $instance = null;

    /**
     * Instantiate the container.
     *
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @param array $values The parameters or objects.
     * @codeCoverageIgnore
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

        self::$instance = $this;
    }

    /**
     * Return the current instance of the container.
     *
     * @return Container
     * @codeCoverageIgnore
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof Container) {
            throw new \InvalidArgumentException(
                'The Mill container must be set up before you can grab an instance of it.'
            );
        }

        return self::$instance;
    }

    /**
     * Return the current instance of the configuration system.
     *
     * @return \Mill\Config
     */
    public static function getConfig()
    {
        return self::getInstance()['config'];
    }

    /**
     * Return the current instance of the filesystem.
     *
     * @return \League\Flysystem\Filesystem
     */
    public static function getFilesystem()
    {
        return self::getInstance()['filesystem'];
    }

    /**
     * Return the current instance of the annotation reader.
     *
     * @return \Closure
     */
    public static function getAnnotationReader()
    {
        return self::getInstance()['reader.annotations'];
    }

    /**
     * Return the current instance of the code annotation reader.
     *
     * @return \Closure
     */
    public static function getCodeReader()
    {
        return self::getInstance()['reader.code'];
    }
}
