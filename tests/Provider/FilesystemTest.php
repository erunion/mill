<?php
namespace Mill\Tests\Provider;

use Mill\Provider\Filesystem;
use Pimple\Container;

class FilesystemTest extends \PHPUnit\Framework\TestCase
{
    public function testRegister(): void
    {
        $container = new Container;
        $filesystem = new Filesystem;
        $filesystem->register($container);

        $this->assertInstanceOf(\League\Flysystem\Filesystem::class, $container['filesystem']);
    }
}
