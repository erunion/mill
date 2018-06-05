<?php
namespace Mill\Tests\Command;

use Mill\Command\Compile;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CompileTest extends \PHPUnit\Framework\TestCase
{
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

    public function testCommand(): void
    {
        /** @var string $output_dir */
        $output_dir = tempnam(sys_get_temp_dir(), 'mill-compile-test-');
        if (file_exists($output_dir)) {
            unlink($output_dir);
        }

        mkdir($output_dir);

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            'output' => $output_dir
        ]);

        $versions = [
            '1.0',
            '1.1',
            '1.1.1',
            '1.1.2',
            '1.1.3'
        ];

        $output = $this->tester->getDisplay();
        $this->assertNotContains('Running a dry run', $output);

        foreach ($versions as $version) {
            $this->assertContains('API version: ' . $version, $output);
        }

        $this->assertSame(array_merge(['.', '..'], $versions), scandir($output_dir));

        $blueprints_dir = __DIR__ . '/../../resources/examples/Showtimes/blueprints';
        $representations = [
            'Coded error',
            'Error',
            'Movie',
            'Person',
            'Theater'
        ];

        $resources = [
            'Movies',
            'Theaters'
        ];

        foreach ($versions as $version) {
            foreach ($representations as $name) {
                $this->assertFileEquals(
                    $blueprints_dir . '/' . $version . '/representations/' . $name . '.apib',
                    $output_dir . '/' . $version . '/representations/' . $name . '.apib',
                    'Compiled representation `' . $name . '.apib` for version `' . $version . '`` does not match.'
                );
            }

            foreach ($resources as $name) {
                $this->assertFileEquals(
                    $blueprints_dir . '/' . $version . '/resources/' . $name . '.apib',
                    $output_dir . '/' . $version . '/resources/' . $name . '.apib',
                    'Compiled resource `' . $name . '.apib` for version `' . $version . '`` does not match.'
                );
            }
        }
    }

    public function testCommandWithDryRun(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--dry-run' => true,
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains('Running a dry run', $output);
        $this->assertContains('API version: 1.0', $output);
        $this->assertContains('API version: 1.1', $output);
    }

    public function testCommandWithDefaultVersion(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--dry-run' => true,
            '--default' => true,
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains('Running a dry run', $output);
        $this->assertNotContains('API version: 1.0', $output);
        $this->assertContains('API version: 1.1', $output);
    }

    public function testCommandWithSpecificConstraint(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--dry-run' => true,
            '--constraint' => '1.0',
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains('Running a dry run', $output);
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
            '--dry-run' => true,
            '--constraint' => '1.^',
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains('1.^', $output);
        $this->assertContains('unrecognized schema', $output);
    }
}
