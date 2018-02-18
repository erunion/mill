<?php
namespace Mill\Tests\Generator;

use Mill\Generator\Blueprint;
use Mill\Tests\TestCase;

class BlueprintTest extends TestCase
{
    const DS = DIRECTORY_SEPARATOR;

    public function testGeneration(): void
    {
        $dir = self::RESOURCES_DIR . 'examples/Showtimes/blueprints/';

        $blueprint = new Blueprint($this->getConfig());
        $generated = $blueprint->generate();

        foreach ($generated as $version => $section) {
            foreach ($section['groups'] as $group => $content) {
                $file = $dir . $version . self::DS . 'resources' . self::DS . str_replace('\\', '-', ucwords($group));
                $expected = file_get_contents($file . '.apib');

                $this->assertSame(
                    $expected,
                    $content,
                    sprintf(
                        'The generated resource `%s`, on version %s does not match the expected content.',
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
                        'The generated representation `%s`, on version %s does not match the expected content.',
                        $representation,
                        $version
                    )
                );
            }
        }
    }

    public function testGenerationWithAnExcludedGroup(): void
    {
        $this->getConfig()->addBlueprintNamespaceExclude('Movies');

        $blueprint = new Blueprint($this->getConfig());
        $generated = $blueprint->generate();

        $this->assertSame([
            '1.0',
            '1.1',
            '1.1.1',
            '1.1.2',
            '1.1.3'
        ], array_keys($generated));

        foreach ($generated as $version => $section) {
            $this->assertArrayNotHasKey('Movies', $section['groups']);
            $this->assertArrayHasKey('Theaters', $section['groups']);
        }

        $this->getConfig()->removeBlueprintNamespaceExclude('Movies');
    }
}
