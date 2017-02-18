<?php
namespace Mill\Tests;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Mill\Config;
use Mill\Container;
use Mill\Parser;
use Mill\Parser\Annotations\FieldAnnotation;

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
     * Given a full response field docblock, return a `FieldAnnotation` object.
     *
     * @param string $docblock
     * @return FieldAnnotation
     */
    protected function getFieldAnnotationFromDocblock($docblock)
    {
        // So we can simplify this test by passing in a full docblock, we can just mimic the work that happens inside
        // the Parser and ResponseParser classes where they clean a given docblock, and explode it into an actionable
        // array
        $clean_docblock = Parser::cleanDocblock($docblock);
        $lines = Parser::getAnnotationsFromDocblock($clean_docblock);

        $annotation = new FieldAnnotation(
            $docblock,
            __CLASS__,
            __METHOD__,
            null,
            [
                'docblock_lines' => $lines
            ]
        );

        return $annotation;
    }
}
