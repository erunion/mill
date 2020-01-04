<?php
namespace Mill\Tests\Compiler\Specification;

use Mill\Compiler\Specification\ApiBlueprint;
use Mill\Tests\TestCase;

class ApiBlueprintTest extends TestCase
{
    public function testCompilation(): void
    {
        $control_dir = static::EXAMPLES_DIR . 'Showtimes/compiled/';

        $compiler = new ApiBlueprint($this->getApplication());
        $compiled = $compiler->getCompiled();

        foreach ($compiled as $version => $section) {
            $version_dir = $control_dir . $version . DIRECTORY_SEPARATOR . 'apiblueprint' . DIRECTORY_SEPARATOR;

            foreach ($section['groups'] as $group => $content) {
                $file = $version_dir . 'resources' . DIRECTORY_SEPARATOR . str_replace('\\', '-', ucwords($group));
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
                $file = $version_dir . 'representations' . DIRECTORY_SEPARATOR . $representation;
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

        $compiler = new ApiBlueprint($this->getApplication());
        $compiled = $compiler->getCompiled();

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
