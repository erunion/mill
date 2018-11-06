<?php
namespace Mill\Tests\Compiler\Specification;

use Mill\Compiler\Specification\OpenApi;
use Mill\Tests\TestCase;

class OpenApiTest extends TestCase
{
    public function testCompilation(): void
    {
        $control_dir = static::RESOURCES_DIR . 'examples/Showtimes/compiled/';

        $compiler = new OpenApi($this->getApplication());
        $compiled = $compiler->getCompiled();

        foreach ($compiled as $version => $spec) {
            $version_dir = $control_dir . $version . DIRECTORY_SEPARATOR . 'openapi';

            $expected = file_get_contents($version_dir . DIRECTORY_SEPARATOR . 'api.yaml');
            $content = OpenApi::getYaml($spec);

            $this->assertSame(
                $expected,
                $content,
                sprintf(
                    'The compiled version %s does not match the expected content.',
                    $version
                )
            );

            $reducer = new OpenApi\TagReducer($spec);
            $reduced = $reducer->reduce();
            foreach ($reduced as $tag => $content) {
                $tag = str_replace('\\', DIRECTORY_SEPARATOR, $tag);
                $tag = str_replace('/', DIRECTORY_SEPARATOR, $tag);

                $file = $version_dir . DIRECTORY_SEPARATOR . 'tags' . DIRECTORY_SEPARATOR . $tag;
                $expected = file_get_contents($file . '.yaml');

                $content = OpenApi::getYaml($content);

                $this->assertSame(
                    $expected,
                    $content,
                    sprintf(
                        'The compiled tag `%s`, on version %s does not match the expected content.',
                        $tag,
                        $version
                    )
                );
            }
        }
    }

    public function testCompilationWithAnExcludedGroup(): void
    {
        $this->getConfig()->addCompilerGroupExclusion('Movies');

        $compiler = new OpenApi($this->getApplication());
        $compiled = $compiler->getCompiled();

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
