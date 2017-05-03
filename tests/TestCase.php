<?php
namespace Mill\Tests;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Mill\Config;
use Mill\Container;
use Mill\Parser;
use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Representation\RepresentationParser;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected static $container;

    /**
     * @var string
     */
    protected static $fixturesDir = __DIR__ . '/../tests/_fixtures/';

    /**
     * @var string
     */
    protected static $resourcesDir = __DIR__ . '/../resources/';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $container = new Container([
            'config.path' => 'mill.xml',
        ]);

        // Overwrite the stock library local filesystem with an in-memory file system we'll use for testing.
        $container->extend('filesystem', function ($filesystem, Container $c) {
            return new Filesystem(new MemoryAdapter);
        });

        $config = file_get_contents(static::$fixturesDir . 'mill.test.xml');
        $container->getFilesystem()->write('mill.xml', $config);

        static::$container = $container;
    }

    /**
     * Return the current unit test instance of our container system.
     *
     * @return Container
     */
    public function getContainer()
    {
        return self::$container;
    }

    /**
     * Return the current unit test instance of our configuration system.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->getContainer()->getConfig();
    }

    /**
     * Return the current unit test instance of the filesystem adapter.
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->getContainer()->getFilesystem();
    }

    /**
     * Given a full response field docblock, return a `DataAnnotation` object.
     *
     * @param string $docblock
     * @param string $class
     * @return DataAnnotation
     */
    protected function getDataAnnotationFromDocblock($docblock, $class)
    {
        $tags = Parser::getAnnotationsFromDocblock($docblock)->getTags();
        $annotations = (new RepresentationParser($class))->parseAnnotations($tags, $docblock);

        $this->assertCount(1, $annotations);

        return array_shift($annotations);
    }

    /**
     * @param mixed $exception
     * @param string $class
     * @param string|null $method
     * @param array $asserts
     * @return void
     */
    protected function assertExceptionAsserts($exception, $class, $method, $asserts = [])
    {
        $this->assertSame($class, $exception->getClass());

        // `@api-data` annotation tests don't set up a RepresentationParser with a method, so we don't need to worry
        // about asserting this.
        if (get_class($this) !== 'Mill\Tests\Parser\Annotations\DataAnnotationTest') {
            $this->assertSame($method, $exception->getMethod());
        }

        foreach ($asserts as $method => $expected) {
            $this->assertSame($expected, $exception->{$method}(), $method . '() does not match expected.');
        }
    }
}
