<?php
namespace Mill\Tests;

use League\Flysystem\Filesystem;
use Mill\Config;
use Mill\Container;

class ContainerTest extends TestCase
{
    public function testContainer(): void
    {
        $container = $this->getContainer();

        $this->assertInstanceOf(Config::class, $container['config']);
        $this->assertInstanceOf(Config::class, $container->getConfig());
        $this->assertInstanceOf(Filesystem::class, $container['filesystem']);
        $this->assertInstanceOf(Filesystem::class, $container->getFilesystem());
    }

    public function testContainerFailsIfNoConfigPathWasSupplied(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Container;
    }
}
