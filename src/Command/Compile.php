<?php
namespace Mill\Command;

use League\Flysystem\Filesystem;
use Mill\Application;
use Mill\Compiler\Specification\OpenApi;
use Mill\Config;
use Mill\Compiler\Specification\ApiBlueprint;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Parser\Version;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Compile extends Application
{
    const FORMAT_API_BLUEPRINT = 'apiblueprint';
    const FORMAT_OPENAPI = 'openapi';

    const FORMATS = [
        self::FORMAT_API_BLUEPRINT,
        self::FORMAT_OPENAPI
    ];

    /** @var Filesystem */
    private $filesystem;

    /**
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('compile')
            ->setDescription('Compile API documentation.')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'API specification format to compile documentation into. Available formats: ' . implode(
                    ', ',
                    array_map(function (string $format): string {
                        return '`' . $format . '`';
                    }, self::FORMATS)
                ),
                self::FORMAT_OPENAPI
            )
            ->addOption(
                'constraint',
                null,
                InputOption::VALUE_OPTIONAL,
                'Version constraint to compile documentation for. eg. "3.*", "3.1 - 3.2"',
                null
            )
            ->addOption(
                'default',
                null,
                InputOption::VALUE_OPTIONAL,
                'Compile just the configured default API version documentation. `defaultApiVersion` in your ' .
                    '`mill.xml` file.',
                false
            )
            ->addArgument('output', InputArgument::REQUIRED, 'Directory to output your compiled documentation in.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $output_dir = realpath($input->getArgument('output'));
        $format = strtolower($input->getOption('format'));
        $version = $input->getOption('constraint');

        if (!in_array($format, ['apiblueprint', 'openapi'])) {
            $output->writeLn('<error>`' . $format . '` is an unknown compilation format.</error>');
            return 1;
        }

        if ($input->getOption('default')) {
            $version = $this->container['config']->getDefaultApiVersion();
        }

        // Validate the current version constraint.
        if (!empty($version)) {
            try {
                $version = new Version($version, __CLASS__, __METHOD__);
            } catch (UnrecognizedSchemaException $e) {
                $output->writeLn('<error>' . $e->getValidationMessage() . '</error>');
                return 1;
            }
        }

        /** @var Config $config */
        $config = $this->container['config'];
        $this->filesystem = $this->container['filesystem'];

        $output->writeln('<comment>Compiling controllers and representations...</comment>');
        if ($format === self::FORMAT_API_BLUEPRINT) {
            $compiler = new ApiBlueprint($config, $version);
        } else {
            $compiler = new OpenApi($config, $version);
        }

        $output->writeln(
            sprintf(
                '<comment>Compiling %s files...</comment>',
                ($format === self::FORMAT_API_BLUEPRINT) ? 'API Blueprint' : 'OpenAPI'
            )
        );

        $compiled = $compiler->compile();
        foreach ($compiled as $version => $spec) {
            $version_dir = $output_dir . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR;

            $output->writeLn('<comment> - API version: ' . $version . '</comment>');

            switch ($format) {
                case self::FORMAT_API_BLUEPRINT:
                    $this->saveApiBlueprint($output, $version_dir, $spec);
                    break;

                case self::FORMAT_OPENAPI:
                    $this->saveOpenApi($output, $version_dir, $spec);
                    break;
            }
        }

        $output->writeln(['', '<success>Done!</success>']);

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param string $version_dir
     * @param array $spec
     */
    private function saveApiBlueprint(OutputInterface $output, string $version_dir, array $spec): void
    {
        // Process resource groups.
        if (isset($spec['groups'])) {
            foreach ($spec['groups'] as $group => $markdown) {
                // Convert any nested groups, like `Me\Videos`, into a proper directory structure: `Me/Videos`.
                $group = str_replace('\\', DIRECTORY_SEPARATOR, $group);

                $this->filesystem->put(
                    $version_dir . 'resources' . DIRECTORY_SEPARATOR . $group . '.apib',
                    trim($markdown)
                );
            }
        }

        // Process data structures.
        if (isset($spec['structures'])) {
            foreach ($spec['structures'] as $structure => $markdown) {
                // Sanitize any structure names with forward slashes to avoid them from being nested in directories
                // by Flysystem.
                $structure = str_replace('/', '-', $structure);

                $this->filesystem->put(
                    $version_dir . 'representations' . DIRECTORY_SEPARATOR . $structure . '.apib',
                    trim($markdown)
                );
            }
        }

        // Save a, single, combined API Blueprint file.
        $this->filesystem->put($version_dir . 'api.apib', $spec['combined']);
    }

    /**
     * @param OutputInterface $output
     * @param string $version_dir
     * @param array $spec
     */
    private function saveOpenApi(OutputInterface $output, string $version_dir, array $spec): void
    {
        $this->filesystem->put(
            $version_dir . 'api.yaml',
            Yaml::dump($spec, PHP_INT_MAX, 4, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE)
        );
    }
}