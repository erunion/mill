<?php
namespace Mill\Tests\Command;

use Mill\Command\Compile;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CompileTest extends \PHPUnit\Framework\TestCase
{
    protected const VERSIONS = [
        '1.0',
        '1.1',
        '1.1.1',
        '1.1.2',
        '1.1.3'
    ];

    protected const REPRESENTATIONS = [
        'Coded error',
        'Error',
        'Movie',
        'Person',
        'Theater'
    ];

    protected const RESOURCES = [
        'Movies',
        'Theaters'
    ];

    /** @var \Symfony\Component\Console\Command\Command */
    protected $command;

    /** @var CommandTester */
    protected $tester;

    /** @var string */
    protected $config_file;

    public function setUp(): void
    {
        $application = new Application();
        $application->add(new Compile);

        $this->command = $application->find('compile');
        $this->tester = new CommandTester($this->command);

        $this->config_file = __DIR__ . '/../../resources/examples/mill.xml';
    }

    public function testCommandForOpenApi(): void
    {
        $output_dir = $this->getTempOutputDirectory('openapi');

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--format' => Compile::FORMAT_OPENAPI,
            'output' => $output_dir
        ]);

        $output = $this->tester->getDisplay();

        foreach (self::VERSIONS as $version) {
            $this->assertContains('API version: ' . $version, $output);
        }

        $this->assertSame(array_merge(['.', '..'], self::VERSIONS), scandir($output_dir));

        $control_dir = __DIR__ . '/../../resources/examples/Showtimes/compiled/';
        foreach (self::VERSIONS as $version) {
            $this->assertFileEquals(
                $control_dir . '/' . $version . '/openapi/api.yaml',
                $output_dir . '/' . $version . '/openapi/api.yaml',
                'Compiled OpenAPI spec for version `' . $version . ' does not match.'
            );

            foreach (self::RESOURCES as $name) {
                $this->assertFileEquals(
                    $control_dir . '/' . $version . '/openapi/tags/' . $name . '.yaml',
                    $output_dir . '/' . $version . '/openapi/tags/' . $name . '.yaml',
                    'Compiled OpenAPI tag spec `' . $name . '.yaml` for version `' . $version . '` does not match.'
                );
            }
        }
    }

    public function testCommandForApiBlueprint(): void
    {
        $output_dir = $this->getTempOutputDirectory('apiblueprint');

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--format' => Compile::FORMAT_API_BLUEPRINT,
            'output' => $output_dir
        ]);

        $output = $this->tester->getDisplay();

        foreach (self::VERSIONS as $version) {
            $this->assertContains('API version: ' . $version, $output);
        }

        $this->assertSame(array_merge(['.', '..'], self::VERSIONS), scandir($output_dir));

        $control_dir = __DIR__ . '/../../resources/examples/Showtimes/compiled/';

        foreach (self::VERSIONS as $version) {
            foreach (self::REPRESENTATIONS as $name) {
                $this->assertFileEquals(
                    $control_dir . '/' . $version . '/apiblueprint/representations/' . $name . '.apib',
                    $output_dir . '/' . $version . '/apiblueprint/representations/' . $name . '.apib',
                    'Compiled representation `' . $name . '.apib` for version `' . $version . '` does not match.'
                );
            }

            foreach (self::RESOURCES as $name) {
                $this->assertFileEquals(
                    $control_dir . '/' . $version . '/apiblueprint/resources/' . $name . '.apib',
                    $output_dir . '/' . $version . '/apiblueprint/resources/' . $name . '.apib',
                    'Compiled resource `' . $name . '.apib` for version `' . $version . '` does not match.'
                );
            }
        }
    }

    /**
     * @dataProvider providerFormats
     */
    public function testCommandWithDefaultVersion(string $format, string $format_name): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--default' => true,
            '--format' => $format,
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains($format_name, $output);
        $this->assertNotContains('API version: 1.0', $output);
        $this->assertContains('API version: 1.1', $output);
    }

    /**
     * @dataProvider providerFormats
     */
    public function testCommandWithSpecificConstraint(string $format, string $format_name): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--constraint' => '1.0',
            '--format' => $format,
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains($format_name, $output);
        $this->assertContains('API version: 1.0', $output);
        $this->assertNotContains('API version: 1.1', $output);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The supplied Mill configuration file does not exist.
     */
    public function testCommandFailsOnInvalidConfigFile(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            'output' => sys_get_temp_dir()
        ]);
    }

    public function testCommandFailsOnInvalidVersionConstraint(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--constraint' => '1.^',
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains('1.^', $output);
        $this->assertContains('unrecognized schema', $output);
    }

    public function testCommandFailsOnInvalidFormat(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--format' => 'raml',
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains('raml', $output);
        $this->assertContains('unknown compilation format', $output);
    }

    /**
     * @return array
     */
    public function providerFormats(): array
    {
        return [
            [Compile::FORMAT_API_BLUEPRINT, 'API Blueprint'],
            [Compile::FORMAT_OPENAPI, 'OpenAPI'],
        ];
    }

    private function getTempOutputDirectory(string $format): string
    {
        /** @var string $output_dir */
        $output_dir = tempnam(sys_get_temp_dir(), 'mill-compile-' . $format . '-test-');
        if (file_exists($output_dir)) {
            unlink($output_dir);
        }

        mkdir($output_dir);

        return $output_dir;
    }
}
