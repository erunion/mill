<?php
namespace Mill\Tests\Command;

use Mill\Command\ErrorMap;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ErrorMapTest extends \PHPUnit\Framework\TestCase
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
        $application->add(new ErrorMap);

        $this->command = $application->find('errors');
        $this->tester = new CommandTester($this->command);

        $this->config_file = __DIR__ . '/../../resources/examples/mill.xml';
    }

    /**
     * @dataProvider providerTestErrorMap
     * @param boolean $private_objects
     * @param array $capabilities
     * @param string $expected_file
     * @return void
     */
    public function testErrorMap($private_objects, $capabilities, $expected_file)
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

        $this->tester->execute($params);

        $versions = [
            '1.0',
            '1.1',
            '1.1.1',
            '1.1.3'
        ];

        $output = $this->tester->getDisplay();
        $this->assertNotContains('Running a dry run', $output);

        $this->assertNotContains('API version: 1.1.2', $output);
        foreach ($versions as $version) {
            $this->assertContains('API version: ' . $version, $output);

            $blueprints_dir = __DIR__ . '/../../resources/examples/Showtimes/blueprints/' . $version;
            $this->assertFileEquals(
                $blueprints_dir . '/' . $expected_file,
                $output_dir . '/' . $version . '/errors.md',
                'Generated error map does not match.'
            );
        }
    }

    public function testGenerateWithDryRun()
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
        $this->assertContains('API version: 1.1.1', $output);
        $this->assertNotContains('API version: 1.1.2', $output);
    }

    public function testGenerateWithDefaultVersion()
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

        // In our test cases, there's no error codes under the default API version, so this shouldn't be creating error
        // maps.
        $this->assertNotContains('API version', $output);
    }

    public function testGenerateWithSpecificConstraint()
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
    public function testGenerateFailsOnInvalidConfigFile()
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            'output' => sys_get_temp_dir()
        ]);
    }

    public function testGenerateFailsOnInvalidVersionConstraint()
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

    /**
     * @return array
     */
    public function providerTestErrorMap()
    {
        return [
            // Complete error map. All documentation parsed.
            'complete-error-map' => [
                'private_objects' => true,
                'capabilities' => [],
                'expected_file' => 'errors.md'
            ],

            // Error map with public-only parsed docs and all capabilities.
            'error-map-public-docs-with-all-capabilities' => [
                'private_objects' => false,
                'capabilities' => [],
                'expected' => 'errors-public-only-all-capabilities.md'
            ],

            // Error map with public-only parsed docs and unmatched capabilities
            'error-map-public-docs-with-unmatched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'BUY_TICKETS',
                    'FEATURE_FLAG'
                ],
                'expected' => 'errors-public-only-unmatched-capabilities.md'
            ],

            // Error map with public-only parsed docs and matched capabilities
            'error-map-public-docs-with-matched-capabilities' => [
                'private_objects' => false,
                'capabilities' => [
                    'DELETE_CONTENT'
                ],
                'expected' => 'errors-public-only-matched-capabilities.md'
            ]
        ];
    }
}
