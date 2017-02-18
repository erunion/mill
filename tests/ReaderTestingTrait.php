<?php
namespace Mill\Tests;

use Mill\Container;
use Mill\Provider\Reader;

trait ReaderTestingTrait
{
    public function setUp()
    {
        $container = Container::getInstance();

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
     * @return void
     */
    protected function overrideReadersWithFakeDocblockReturn($docblock)
    {
        $container = Container::getInstance();

        $container->extend('reader.annotations', function ($reader, Container $c) use ($docblock) {
            return function () use ($docblock) {
                return $docblock;
            };
        });

        $container->extend('reader.annotations.representation', function ($reader, Container $c) use ($docblock) {
            return function () use ($docblock) {
                return $docblock;
            };
        });
    }
}
