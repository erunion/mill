<?php
namespace Mill\Tests;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Mill\Application;
use Mill\Config;
use Mill\Container;
use Mill\Exceptions\BaseException;
use Mill\Parser;
use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Representation\RepresentationParser;

class TestCase extends \PHPUnit\Framework\TestCase
{
    const FIXTURES_DIR = __DIR__ . '/../tests/_fixtures/';
    const RESOURCES_DIR = __DIR__ . '/../resources/';

    /** @var Container */
    protected static $container;

    /** @var Application */
    protected $application;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $container = new Container([
            'config.path' => 'mill.xml',
        ]);

        // Overwrite the stock library local filesystem with an in-memory file system we'll use for testing.
        $container->extend(
            'filesystem',
            function (Filesystem $filesystem, Container $c): Filesystem {
                return new Filesystem(new MemoryAdapter);
            }
        );

        $config = file_get_contents(self::FIXTURES_DIR . 'mill.test.xml');
        $container->getFilesystem()->write('mill.xml', $config);

        static::$container = $container;
    }

    public function setUp()
    {
        parent::setUp();

        $this->application = new Application($this->getConfig());
        $this->application->preload();
    }

    /**
     * Return the current unit test instance of our container system.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return self::$container;
    }

    /**
     * Return the current unit test instance of our configuration system.
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->getContainer()->getConfig();
    }

    /**
     * Return the current unit test instance of the filesystem adapter.
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
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
    protected function getDataAnnotationFromDocblock(string $docblock, string $class): DataAnnotation
    {
        $tags = Parser::getAnnotationsFromDocblock($docblock)->getTags()->toArray();

        $parser = new RepresentationParser($class);
        $parser->setMethod(__METHOD__);
        $annotations = $parser->parseAnnotations($tags, $docblock);

        $this->assertCount(1, $annotations);

        return array_shift($annotations);
    }

    protected function assertExceptionAsserts(
        BaseException $exception,
        //string $class,
        //?string $method,
        array $asserts = []
    ): void {
        //$this->assertSame($class, $exception->getClass());

        // `@api-data` annotation tests don't set up a RepresentationParser with a method, so we don't need to worry
        // about asserting this.
        /*if (get_class($this) !== 'Mill\Tests\Parser\Annotations\DataAnnotationTest') {
            $this->assertSame($method, $exception->getMethod());
        }*/

        foreach ($asserts as $method => $expected) {
            $this->assertSame($expected, $exception->{$method}(), $method . '() does not match expected.');
        }
    }
}
