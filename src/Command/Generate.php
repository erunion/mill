<?php
namespace Mill\Command;

use Mill\Container;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Generator\Blueprint;
use Mill\Parser\Version;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate command for generating API Blueprint documentation.
 *
 */
class Generate extends Command
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('generate')
            ->setDescription('Generate API Blueprint documentation.')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to your `mill.xml` config file.',
                'mill.xml'
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
                'Generate just the configured default API version documentation. `defaultApiVersion` in your ' .
                    '`mill.xml` file.',
                false
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Execute a dry run (do not save any files).',
                false
            )
            ->addArgument('output', InputArgument::REQUIRED, 'Directory to output your generated `.apib` files in.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('green', null, ['bold']);
        $output->getFormatter()->setStyle('success', $style);

        $output_dir = realpath($input->getArgument('output'));
        $config_file = realpath($input->getOption('config'));
        $version = $input->getOption('constraint');
        $dry_run = $input->getOption('dry-run');

        // Generate!
        // @todo This should be pulled from the core Application instead, so we can inject the dependency in tests.
        $container = new Container([
            'config.path' => $config_file
        ]);

        if ($dry_run) {
            $output->writeln('<info>Running a dry run…</info>');
        }

        if ($input->getOption('default')) {
            $version = $container['config']->getDefaultApiVersion();
        }

        // Validate the current version generation constraint.
        if (!empty($version)) {
            try {
                $version = new Version($version, __CLASS__, __METHOD__);
            } catch (UnrecognizedSchemaException $e) {
                $output->writeLn('<error>' . $e->getValidationMessage() . '</error>');
                return;
            }
        }

        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = $container['filesystem'];

        $output->writeln('<comment>Compiling controllers and representations…</comment>');
        $generator = new Blueprint($container['config'], $version);

        $output->writeln('<comment>Generating API Blueprint files…</comment>');
        $blueprints = $generator->generate();

        foreach ($blueprints as $version => $section) {
            $version_dir = $output_dir . self::DS . $version . self::DS;

            $output->writeLn('<comment> - API version: ' . $version . '</comment>');

            $total_work = (isset($section['groups'])) ? count($section['groups']) : 0;
            $total_work += (isset($section['structures'])) ? count($section['structures']) : 0;

            $progress = new ProgressBar($output, $total_work);
            $progress->setFormatDefinition('custom', ' %current%/%max% [%bar%] %message%');
            $progress->setFormat('custom');
            $progress->start();

            // Process resource groups.
            if (isset($section['groups'])) {
                $progress->setMessage('Processing resources…');
                foreach ($section['groups'] as $group => $markdown) {
                    $progress->advance();

                    if ($dry_run) {
                        continue;
                    }

                    // Convert any nested group names, like `Me\Videos`, into a proper directory structure: `Me/Videos`.
                    $group = str_replace('\\', self::DS, $group);

                    $filesystem->put(
                        $version_dir . 'resources' . self::DS . $group . '.apib',
                        trim($markdown)
                    );
                }
            }

            // Process data structures.
            if (isset($section['structures'])) {
                $progress->setMessage('Processing representations…');
                foreach ($section['structures'] as $structure => $markdown) {
                    $progress->advance();

                    if ($dry_run) {
                        continue;
                    }

                    // Sanitize any structure names with forward slashes to avoid them from being nested in directories
                    // by Flysystem.
                    $structure = str_replace('/', '-', $structure);

                    $filesystem->put(
                        $version_dir . 'representations' . self::DS . $structure . '.apib',
                        trim($markdown)
                    );
                }
            }

            // Save a, single, combined API Blueprint file.
            if (!$dry_run) {
                $filesystem->put(
                    $version_dir . 'api.apib',
                    $section['combined']
                );
            }

            $progress->setMessage('');
            $progress->finish();
            $output->writeLn('');
        }

        $output->writeln(['', '<success>Done!</success>']);
    }
}
