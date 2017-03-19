<?php
namespace Mill\Tests\Generator;

use Mill\Generator\Blueprint;
use Mill\Tests\TestCase;

class BlueprintTest extends TestCase
{
    const DS = DIRECTORY_SEPARATOR;

    public function testGeneration()
    {
        $dir = static::$resourcesDir . 'examples/Showtimes/blueprints/';

        $blueprint = new Blueprint($this->getConfig());
        $generated = $blueprint->generate();

        foreach ($generated as $version => $section) {
            foreach ($section['groups'] as $group => $content) {
                $file = $dir . $version . self::DS . 'resources' . self::DS . str_replace('\\', '-', ucwords($group));
                $expected = file_get_contents($file . '.apib');

                $this->assertSame(
                    $expected,
                    $content,
                    sprintf('The generated resource `%s`, on version %s does not match the expected content.',
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
                    sprintf('The generated representation `%s`, on version %s does not match the expected content.',
                        $representation,
                        $version
                    )
                );
            }
        }
    }
}
