<?php
namespace Mill\Tests\Command;

use Mill\Command\Lint;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class LintTest extends \PHPUnit_Framework_TestCase
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
        $application->add(new Lint);

        $this->command = $application->find('lint');
        $this->tester = new CommandTester($this->command);

        $this->config_file = __DIR__ . '/../../resources/examples/mill.xml';
    }

    public function testCommand()
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file
        ]);

        $output = $this->tester->getDisplay();

        $this->assertNotContains('Exceptions', $output);
        $this->assertContains('No errors found.', $output);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The supplied Mill configuration file does not exist.
     */
    public function testCommandFailsOnInvalidConfigFile()
    {
        $this->tester->execute([
            'command' => $this->command->getName()
        ]);
    }
}
