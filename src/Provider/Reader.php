<?php
namespace Mill\Provider;

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
        $container['reader.annotations'] = function (Container $c): \Mill\Parser\Reader {
            return new \Mill\Parser\Reader;
        };

        /*$container['reader.annotations.representation'] = function (Container $c): Closure {
            return function (string $class, string $method): string {
                return (new \Mill\Reader)->getRepresentationAnnotations($class, $method);
            };
        };*/
    }
}
