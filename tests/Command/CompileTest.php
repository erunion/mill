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
                $control_dir . '/' . $version . '/openapi/api.json',
                $output_dir . '/' . $version . '/openapi/api.json',
                'Compiled OpenAPI spec for version `' . $version . '` does not match.'
            );

            foreach (self::RESOURCES as $name) {
                $this->assertFileEquals(
                    $control_dir . '/' . $version . '/openapi/tags/' . $name . '.json',
                    $output_dir . '/' . $version . '/openapi/tags/' . $name . '.json',
                    'Compiled OpenAPI tag spec `' . $name . '.json` for version `' . $version . '` does not match.'
                );
            }
        }
    }

    public function testCommandForOpenApiOnSpecificEnvironment(): void
    {
        $output_dir = $this->getTempOutputDirectory('openapi');

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--format' => Compile::FORMAT_OPENAPI,
            '--environment' => 'prod',
            'output' => $output_dir
        ]);

        $output = $this->tester->getDisplay();

        $this->assertContains('`prod` environment', $output);

        foreach (self::VERSIONS as $version) {
            $output = file_get_contents($output_dir . '/' . $version . '/openapi/api.json');

            $this->assertContains('"url": "https:\/\/api.example.com"', $output);
            $this->assertContains('Production', $output);

            $this->assertNotContains('"url": "https:\/\/api.example.local"', $output);
            $this->assertNotContains('Development', $output);
        }
    }

    public function testCommandForOpenApiWithPublicOnlyDocs(): void
    {
        $output_dir = $this->getTempOutputDirectory('openapi-public-only');

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--format' => Compile::FORMAT_OPENAPI,
            '--private' => false,
            'output' => $output_dir
        ]);

        $output = $this->tester->getDisplay();

        foreach (self::VERSIONS as $version) {
            $this->assertContains('API version: ' . $version, $output);

            $json = file_get_contents($output_dir . '/' . $version . '/openapi/api.json');
            $spec = json_decode($json, true);

            // Since `DELETE /movies/+id` is a private endpoint, it should not be present under any version.
            $this->assertArrayNotHasKey(
                'delete',
                $spec['paths']['/movies/{id}'],
                $version . ' should not have `DELETE /movies/+id'
            );
        }
    }

    public function testCommandForOpenApiForPublicConsumption(): void
    {
        $output_dir = $this->getTempOutputDirectory('openapi-public-consumption');

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--format' => Compile::FORMAT_OPENAPI,
            '--for_public_consumption' => true,
            'output' => $output_dir
        ]);

        $output = $this->tester->getDisplay();

        foreach (self::VERSIONS as $version) {
            $this->assertContains('API version: ' . $version, $output);

            // Grouped specifications should not be present.
            $this->assertDirectoryNotExists($output_dir . '/' . $version . '/openapi');

            $json = file_get_contents($output_dir . '/' . $version . '/api.json');
            $spec = json_decode($json, true);

            // Since `DELETE /movies/+id` is a private endpoint, it should not be present under any version.
            $this->assertArrayNotHasKey(
                'delete',
                $spec['paths']['/movies/{id}'],
                $version . ' should not have `DELETE /movies/+id'
            );

            // Vendor extensions should not be present anywhere within the spec.
            $this->assertNotContains('x-mill-path-aliased', $json);
            $this->assertNotContains('x-mill-path-aliases', $json);
            $this->assertNotContains('x-mill-vendor-tags', $json);
            $this->assertNotContains('x-mill-visibility-private', $json);
        }
    }

    public function testCommandForOpenApiWithPublicOnlyDocsAndAVendorTag(): void
    {
        $output_dir = $this->getTempOutputDirectory('openapi-public-only-with-vendor-tag');

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--format' => Compile::FORMAT_OPENAPI,
            '--private' => false,
            '--vendor_tag' => [
                'tag:DELETE_CONTENT'
            ],
            'output' => $output_dir
        ]);

        $output = $this->tester->getDisplay();

        foreach (self::VERSIONS as $version) {
            $this->assertContains('API version: ' . $version, $output);

            $json = file_get_contents($output_dir . '/' . $version . '/openapi/api.json');
            $spec = json_decode($json, true);

            // `DELETE /movies/{id}` isn't available on either of these versions, so it continue to not show up in our
            // compiled OAS.
            if ($version === '1.0' || $version === '1.1.3') {
                $this->assertArrayNotHasKey(
                    'delete',
                    $spec['paths']['/movies/{id}'],
                    $version . ' should not have `DELETE /movies/+id'
                );

                continue;
            }

            // While `DELETE /movies/{id}` is private, since we're looking for content that's public but also locked
            // behind `tag:DELETE_CONTENT`, `DELETE /movies/{id}` should be present.
            $this->assertArrayHasKey(
                'delete',
                $spec['paths']['/movies/{id}'],
                $version . ' should not have `DELETE /movies/+id'
            );
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
                $output_file = $output_dir . '/' . $version . '/apiblueprint/representations/' . $name . '.apib';

                // Coded error is not available under 1.1.2
                if ($name === 'Coded error' && $version === '1.1.2') {
                    $this->assertFileNotExists($output_file);
                    continue;
                }

                $this->assertFileEquals(
                    $control_dir . '/' . $version . '/apiblueprint/representations/' . $name . '.apib',
                    $output_file,
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
     * @param string $format
     * @param string $format_name
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
     * @param string $format
     * @param string $format_name
     */
    public function testCommandWithLatestVersion(string $format, string $format_name): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--latest' => true,
            '--format' => $format,
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains($format_name, $output);
        $this->assertNotContains('API version: 1.1.2', $output);
        $this->assertContains('API version: 1.1.3', $output);
    }

    /**
     * @dataProvider providerFormats
     * @param string $format
     * @param string $format_name
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

    public function testCommandFailsOnInvalidEnvironment(): void
    {
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--config' => $this->config_file,
            '--format' => 'openapi',
            '--environment' => 'production',
            'output' => sys_get_temp_dir()
        ]);

        $output = $this->tester->getDisplay();
        $this->assertContains('environment has not been configured', $output);
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
