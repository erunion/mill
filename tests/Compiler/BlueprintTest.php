<?php
namespace Mill\Tests\Compiler;

use Mill\Compiler\Blueprint;
use Mill\Tests\TestCase;

class BlueprintTest extends TestCase
{
    const DS = DIRECTORY_SEPARATOR;

    public function testCompilation(): void
    {
        $dir = static::$resourcesDir . 'examples/Showtimes/blueprints/';

        $compiler = new Blueprint($this->getConfig());
        $compiled = $compiler->compile();

        foreach ($compiled as $version => $section) {
            foreach ($section['groups'] as $group => $content) {
                $file = $dir . $version . self::DS . 'resources' . self::DS . str_replace('\\', '-', ucwords($group));
                $expected = file_get_contents($file . '.apib');

                $this->assertSame(
                    $expected,
                    $content,
                    sprintf(
                        'The compiled resource `%s`, on version %s does not match the expected content.',
                        $group,
                        $version
                    )
                );
            }

            foreach ($section['structures'] as $representation => $content) {
                $file = $dir . $version . self::DS . 'representations' . self::DS . $representation;
                $expected = file_get_contents($file . '.apib');

                $this->assertSame(
                    $expected,
                    $content,
                    sprintf(
                        'The compiled representation `%s`, on version %s does not match the expected content.',
                        $representation,
                        $version
                    )
                );
            }
        }
    }

    public function testCompilationWithAnExcludedGroup(): void
    {
        $this->getConfig()->addCompilerGroupExclusion('Movies');

        $compiler = new Blueprint($this->getConfig());
        $compiled = $compiler->compile();

        $this->assertSame([
            '1.0',
            '1.1',
            '1.1.1',
            '1.1.2',
            '1.1.3'
        ], array_keys($compiled));

        foreach ($compiled as $version => $section) {
            $this->assertArrayNotHasKey('Movies', $section['groups']);
            $this->assertArrayHasKey('Theaters', $section['groups']);
        }

        $this->getConfig()->removeCompilerGroupExclusion('Movies');
    }
}
