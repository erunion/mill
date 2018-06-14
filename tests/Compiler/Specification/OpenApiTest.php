<?php
namespace Mill\Tests\Compiler\Specification;

use Mill\Compiler\Specification\OpenApi;
use Mill\Tests\TestCase;
use Symfony\Component\Yaml\Yaml;

class OpenApiTest extends TestCase
{
    public function testCompilation(): void
    {
        $control_dir = static::$resourcesDir . 'examples/Showtimes/compiled/';

        $compiler = new OpenApi($this->getConfig());
        $compiled = $compiler->compile();

        foreach ($compiled as $version => $spec) {
            $expected = file_get_contents($control_dir . $version . DIRECTORY_SEPARATOR . 'api.yaml');
            $content = Yaml::dump($spec, PHP_INT_MAX, 2, true);

            $this->assertSame(
                $expected,
                $content,
                sprintf(
                    'The compiled version %s does not match the expected content.',
                    $version
                )
            );
        }
    }

    public function testCompilationWithAnExcludedGroup(): void
    {
        $this->getConfig()->addCompilerGroupExclusion('Movies');

        $compiler = new OpenApi($this->getConfig());
        $compiled = $compiler->compile();

        $this->assertSame([
            '1.0',
            '1.1',
            '1.1.1',
            '1.1.2',
            '1.1.3'
        ], array_keys($compiled));

        foreach ($compiled as $version => $spec) {
            $this->assertSame([
                ['name' => 'Theaters']
            ], $spec['tags']);
        }

        $this->getConfig()->removeCompilerGroupExclusion('Movies');
    }
}
