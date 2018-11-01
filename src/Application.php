<?php
namespace Mill;

class Application
{
    /**
     * When building out dot-notation annotation keys for compiling documentation we use this key to designate the
     * content of an annotations' data.
     *
     * @var string
     */
    const DOT_NOTATION_ANNOTATION_DATA_KEY = '__NESTED_DATA__';

    /**
     * When building out dot-notation annotation keys for compiling documentation we use this key to designate the
     * type of parameter that it is.
     *
     * @var string
     */
    const DOT_NOTATION_ANNOTATION_PARAMETER_TYPE_KEY = '__PARAMETER_TYPE__';

    /** @var Container */
    protected $container;

    /**
     * @param string $config_path
     * @param bool $load_bootstrap
     */
    public function __construct(string $config_path, bool $load_bootstrap = true)
    {
        $this->container = new Container([
            'config.path' => $config_path,
            'config.load_bootstrap' => $load_bootstrap
        ]);
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @param Container $container
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->container->getConfig();
    }
}
