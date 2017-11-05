<?php
namespace Mill\Provider;

use Closure;
use Pimple\Container;

class Reader implements \Pimple\ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * @psalm-suppress MissingClosureReturnType Psalm has trouble with closures returning anonymous functions.
     * @param Container $container A Pimple container instance
     * @return void
     */
    public function register(Container $container)
    {
        $container['reader.annotations'] = function (Container $c): Closure {
            return function (string $class, string $method = null) {
                return (new \Mill\Reader)->getAnnotations($class, $method);
            };
        };

        $container['reader.annotations.representation'] = function (Container $c): Closure {
            return function (string $class, string $method): string {
                return (new \Mill\Reader)->getRepresentationAnnotations($class, $method);
            };
        };
    }
}
