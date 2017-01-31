<?php
namespace Mill\Tests\Generator;

use Mill\Generator\Blueprint;
use Mill\Tests\TestCase;

class BlueprintTest extends TestCase
{
    public function testGeneration()
    {
        $dir = static::$resourcesDir . 'examples/Showtimes/blueprints/';

        $blueprint = new Blueprint($this->getConfig());
        $generated = $blueprint->generate();

        foreach ($generated as $version => $groups) {
            foreach ($groups as $group => $content) {
                $file = $dir . $version . DIRECTORY_SEPARATOR . str_replace('\\', '-', ucwords($group)) . '.apib';
                $expected = file_get_contents($file);

                $this->assertSame(
                    $expected,
                    $content,
                    'The generated `' . $group . '`, on version ' . $version . ' does not match the expected content.'
                );
            }
        }
    }
}
