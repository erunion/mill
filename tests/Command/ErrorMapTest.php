<?php
namespace Mill\Tests\Command;

use Mill\Command\ErrorMap;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ErrorMapTest extends \PHPUnit\Framework\TestCase
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
        $application->add(new ErrorMap);

        $this->command = $application->find('errors');
        $this->tester = new CommandTester($this->command);

        $this->config_file = __DIR__ . '/../../resources/examples/mill.xml';
    }

    /**
     * @dataProvider providerTestCommand
     * @param bool $private_objects
     * @param array $vendor_tags
     * @param string $expected_file
     */
    public function testCommand(bool $private_objects, array $vendor_tags, string $expected_file): void
    {
        /** @var string $output_dir */
        $output_dir = tempnam(sys_get_temp_dir(), 'mill-errormap-test-');
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

        if (!empty($vendor_tags)) {
            $params['--vendor_tag'] = $vendor_tags;
        }

        $this->tester->execute($params);

        $versions = [
            '1.0',
            '1.1',
            '1.1.1',
            '1.1.3'
        ];

        $output = $this->tester->getDisplay();

        $this->assertStringNotContainsString('API version: 1.1.2', $output);
        foreach ($versions as $version) {
            $this->assertStringContainsString('API version: ' . $version, $output);

            $control_dir = __DIR__ . '/../../resources/examples/Showtimes/compiled/' . $version;
            $this->assertFileEquals(
                $control_dir . '/' . $expected_file,
                $output_dir . '/' . $version . '/errors.md',
                'Compiled error map does not match.'
            );
        }
    }

    public function testCommandWithDefaultVersion(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--default' => true,
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();

        // In our test cases, there's no error codes under the default API version, so this shouldn't be creating error
        // maps.
        $this->assertStringNotContainsString('API version', $output);
    }

    public function testCommandWithSpecificConstraint(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--constraint' => '1.0',
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('API version: 1.0', $output);
        $this->assertStringNotContainsString('API version: 1.1', $output);
    }

    public function testCommandFailsOnInvalidConfigFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The supplied Mill configuration file does not exist.');

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
        $this->assertStringContainsString('1.^', $output);
        $this->assertStringContainsString('unrecognized schema', $output);
    }

    public function providerTestCommand(): array
    {
        return [
            // Complete error map. All documentation parsed.
            'complete-error-map' => [
                'private_objects' => true,
                'vendor_tags' => [],
                'expected_file' => 'errors.md'
            ],

            // Error map with public-only parsed docs and all vendor tags.
            'error-map-public-docs-with-all-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [],
                'expected' => 'errors-public-only-all-vendor-tags.md'
            ],

            // Error map with public-only parsed docs and unmatched vendor tags.
            'error-map-public-docs-with-unmatched-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:BUY_TICKETS',
                    'tag:FEATURE_FLAG'
                ],
                'expected' => 'errors-public-only-unmatched-vendor-tags.md'
            ],

            // Error map with public-only parsed docs and matched vendor tags.
            'error-map-public-docs-with-matched-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'DELETE_CONTENT'
                ],
                'expected' => 'errors-public-only-matched-vendor-tags.md'
            ]
        ];
    }
}
