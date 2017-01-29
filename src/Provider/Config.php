<?php
namespace Mill\Provider;

use Pimple\Container;

class Config implements \Pimple\ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * @param Container $container A Pimple container instance
     * @return void
     */
    public function register(Container $container)
    {
        $container['config'] = function (Container $c) {
            return \Mill\Config::loadFromXML(
                $c['filesystem'],
                $c['config.path'],
                $c['config.load_bootstrap']
            );
        };
    }
}
