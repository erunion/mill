<?php
namespace Mill\Provider;

use League\Flysystem\Adapter\Local;
use Pimple\Container;

class Filesystem implements \Pimple\ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * @param Container $container A Pimple container instance
     * @return void
     */
    public function register(Container $container)
    {
        $container['filesystem'] = function (Container $c) {
            $adapter = new Local('/');

            return new \League\Flysystem\Filesystem($adapter);
        };
    }
}
