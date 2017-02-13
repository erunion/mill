<?php
namespace Mill\Provider;

use Pimple\Container;

class Reader implements \Pimple\ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * @param Container $container A Pimple container instance
     * @return void
     */
    public function register(Container $container)
    {
        $container['reader'] = function (Container $c) {
            return function ($class, $method = null) {
                return (new \Mill\Reader)->getAnnotations($class, $method);
            };
        };
    }
}
