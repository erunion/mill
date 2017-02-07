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
        $config = realpath($input->getOption('config'));
        $version = $input->getOption('constraint');
        $dry_run = $input->getOption('dry-run');

        // Generate!
        $container = new Container([
            'config.path' => $config
        ]);

        if ($dry_run) {
            $output->writeln('<info>Running a dry run...</info>');
        }

        if ($input->getOption('default')) {
            $version = Container::getConfig()->getDefaultApiVersion();
        }

        // Validate the current version generation constraint.
        if (!empty($version)) {
            try {
                $version = new Version($version, __CLASS__, __METHOD__);
            } catch (UnrecognizedSchemaException $e) {
                $output->writeLn('<error>' . $e->getValidationMessage() . '</error>');
                exit(1);
            }
        }

        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = $container['filesystem'];

        $output->writeln('<comment>Compiling controllers and representations...</comment>');
        $generator = new Blueprint($container['config'], $version);

        $output->writeln('<comment>Generating API Blueprint files...</comment>');
        $blueprints = $generator->generate();

        foreach ($blueprints as $version => $groups) {
            $output->writeLn('<comment> - API version: ' . $version . '</comment>');

            $progress = new ProgressBar($output, count($groups));
            $progress->start();

            foreach ($groups as $group => $markdown) {
                $progress->advance();

                if ($dry_run) {
                    continue;
                }

                // Convert any nested group names, like `Me\Videos`, into a proper directory structure: `Me/Videos`.
                if (strpos($group, '\\') !== false) {
                    $group = str_replace('\\', DIRECTORY_SEPARATOR, $group);
                }

                $filesystem->put(
                    $output_dir . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR . $group . '.apib',
                    $markdown
                );
            }

            $progress->finish();
            $output->writeLn('');
        }

        $output->writeln(['', '<success>Done!</success>']);
    }
}
