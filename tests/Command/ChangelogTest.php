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

    /**
     * @dataProvider providerTestChangelog
     * @param boolean $private_objects
     * @param array $capabilities
     * @param string $expected_file
     * @return void
     */
    public function testChangelog($private_objects, $capabilities, $expected_file)
    {
        $output_dir = tempnam(sys_get_temp_dir(), 'mill-generate-test-');
        if (file_exists($output_dir)) {
            unlink($output_dir);
        }

        mkdir($output_dir);

        $params = [
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            'output' => $output_dir
        ];

        if (!$private_objects) {
            $params['--private'] = $private_objects;
        }

        if (!empty($capabilities)) {
            $params['--capability'] = $capabilities;
        }

//print_r($params);exit;

        $this->tester->execute($params);

        $blueprints_dir = __DIR__ . '/../../resources/examples/Showtimes/blueprints';

//print_r(file_get_contents($blueprints_dir . '/' . $expected_file));

        $this->assertSame(
            file_get_contents($blueprints_dir . '/' . $expected_file),
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

    /**
     * @return array
     */
    public function providerTestChangelog()
    {
        return [
            // Complete changelog. All documentation parsed.
            'complete-changelog' => [
                'private_objects' => true,
                'capabilities' => [],
                'expected_file' => 'changelog.md'
            ],

            // Changelog with public-only parsed docs and all capabilities.
            'changelog-public-docs-with-all-capabilities' => [
                'private_objects' => false,
                'capabilities' => [],
                'expected' => 'changelog-public-only-all-capabilities.md'
            ],

            // Changelog with public-only parsed docs and unmatched capabilities
            'changelog-public-docs-with-unmatched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'BUY_TICKETS',
                    'FEATURE_FLAG'
                ],
                'expected' => 'changelog-public-only-unmatched-capabilities.md'
            ],

            // Changelog with public-only parsed docs and matched capabilities
            'changelog-public-docs-with-matched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'DELETE_CONTENT'
                ],
                'expected' => 'changelog-public-only-matched-capabilities.md'
            ]
        ];
    }
}
