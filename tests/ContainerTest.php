<?php
namespace Mill\Tests;

use Mill\Container;

class ContainerTest extends TestCase
{
    public function testContainer(): void
    {
        $container = $this->getContainer();

        $this->assertInstanceOf('Mill\Container', $container);
        $this->assertInstanceOf('Mill\Config', $container['config']);
        $this->assertInstanceOf('Mill\Config', $container->getConfig());
        $this->assertInstanceOf('League\Flysystem\Filesystem', $container['filesystem']);
        $this->assertInstanceOf('League\Flysystem\Filesystem', $container->getFilesystem());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainerFailsIfNoConfigPathWasSupplied(): void
    {
        new Container;
    }
}
