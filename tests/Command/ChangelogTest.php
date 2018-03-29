<?php
namespace Mill\Tests\Command;

use Mill\Command\Changelog;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ChangelogTest extends \PHPUnit\Framework\TestCase
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
        $application->add(new Changelog);

        $this->command = $application->find('changelog');
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

        if (!empty($vendor_tags)) {
            $params['--vendor_tag'] = $vendor_tags;
        }

        $this->tester->execute($params);

        $blueprints_dir = __DIR__ . '/../../resources/examples/Showtimes/blueprints';
        $this->assertFileEquals(
            $blueprints_dir . '/' . $expected_file,
            $output_dir . '/changelog.md',
            'Generated changelog does not match.'
        );
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

    public function providerTestCommand(): array
    {
        return [
            // Complete changelog. All documentation parsed.
            'complete-changelog' => [
                'private_objects' => true,
                'vendor_tags' => [],
                'expected_file' => 'changelog.md'
            ],

            // Changelog with public-only parsed docs and all vendor tags.
            'changelog-public-docs-with-all-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [],
                'expected' => 'changelog-public-only-all-vendor-tags.md'
            ],

            // Changelog with public-only parsed docs and matched `tag:BUY_TICKETS` and `tag:FEATURE_FLAG` vendor tags.
            'changelog-public-only-matched-with-tickets-and-feature-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:BUY_TICKETS',
                    'tag:FEATURE_FLAG'
                ],
                'expected' => 'changelog-public-only-matched-with-tickets-and-feature-vendor-tags.md'
            ],

            // Changelog with public-only parsed docs and matched the `tag:DELETE_CONTENT` vendor tag.
            'changelog-public-only-matched-with-delete-vendor-tags' => [
                'private_objects' => false,
                'vendor_tags' => [
                    'tag:DELETE_CONTENT'
                ],
                'expected' => 'changelog-public-only-matched-with-delete-vendor-tags.md'
            ]
        ];
    }
}
