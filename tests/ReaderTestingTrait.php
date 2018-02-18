<?php
namespace Mill\Tests;

use Closure;
use Mill\Container;
use Mill\Provider\Reader;

trait ReaderTestingTrait
{
    public function setUp()
    {
        parent::setUp();

        $container = Container::getInstance();

        // We're setting custom Readers in a number of tests, so let's just quickly reset it and re-register before
        // running any tests.
        unset($container['reader.annotations']);
        //unset($container['reader.annotations.representation']);

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
        $container = Container::getInstance();

        $container->extend(
            'reader.annotations',
            function (\Mill\Parser\Reader $reader, Container $c) use ($docblock): \Mill\Parser\Reader {
                $reader = \Mockery::mock(\Mill\Parser\Reader::class)
                    ->shouldAllowMockingProtectedMethods()
                    ->makePartial();

                $reader->shouldReceive('readFile')->andReturn($docblock);

                return $reader;
            }
        );

        /*$container->extend(
            'reader.annotations.representation',
            function (Closure $reader, Container $c) use ($docblock): Closure {
                return function () use ($docblock): string {
                    return $docblock;
                };
            }
        );*/
    }
}
