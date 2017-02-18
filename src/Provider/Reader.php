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
        $container['reader.annotations'] = function (Container $c) {
            return function ($class, $method = null) {
                return (new \Mill\Reader)->getAnnotations($class, $method);
            };
        };

        $container['reader.code'] = function (Container $c) {
            return function ($class, $method) {
                return (new \Mill\Reader)->getCodeAnnotations($class, $method);
            };
        };
    }
}
