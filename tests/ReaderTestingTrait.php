<?php
namespace Mill\Tests;

use Closure;
use Mill\Container;
use Mill\Provider\Reader;

trait ReaderTestingTrait
{
    public function setUp(): void
    {
        parent::setUp();

        /** @var Container $container */
        $container = $this->getApplication()->getContainer();

        // We're setting custom Readers in a number of tests, so let's just quickly reset it and re-register before
        // running any tests.
        unset($container['reader.annotations']);
        unset($container['reader.annotations.representation']);
        $container->register(new Reader());
    }

    /**
     * Override the currently registered Reader provider with a fake closure that will return a given docblock.
     *
     * We're doing this with some unit tests so we don't have to actually have files with bad annotations lying around
     * in order to test parser failures.
     *
     * @param string $docblock
     */
    protected function overrideReadersWithFakeDocblockReturn(string $docblock): void
    {
        /** @var Container $container */
        $container = $this->getApplication()->getContainer();

        $container->extend(
            'reader.annotations',
            function (Closure $reader, Container $c) use ($docblock): Closure {
                return function () use ($docblock): string {
                    return $docblock;
                };
            }
        );

        $container->extend(
            'reader.annotations.representation',
            function (Closure $reader, Container $c) use ($docblock): Closure {
                return function () use ($docblock): string {
                    return $docblock;
                };
            }
        );
    }
}
