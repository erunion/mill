<?php
namespace Mill\Tests\Command;

use Mill\Command\Changelog;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ChangelogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\Console\Command\Command
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $tester;

    /**
     * @var string
     */
    protected $config_file;

    public function setUp()
    {
        $application = new Application();
        $application->add(new Changelog);

        $this->command = $application->find('changelog');
        $this->tester = new CommandTester($this->command);

        $this->config_file = __DIR__ . '/../../resources/examples/mill.xml';
    }

    public function testChangelog()
    {
        $output_dir = tempnam(sys_get_temp_dir(), 'mill-generate-test-');
        if (file_exists($output_dir)) {
            unlink($output_dir);
        }

        mkdir($output_dir);

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            'output' => $output_dir
        ]);

        $blueprints_dir = __DIR__ . '/../../resources/examples/Showtimes/blueprints';
        $this->assertSame(
            file_get_contents($blueprints_dir . '/changelog.md'),
            file_get_contents($output_dir . '/changelog.md'),
            'Generated changelog does not match.'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The supplied Mill configuration file does not exist.
     */
    public function testGenerateFailsOnInvalidConfigFile()
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            'output' => sys_get_temp_dir()
        ]);
    }
}
