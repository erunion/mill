<?php
namespace Mill\Command;

use Mill\Config;
use Mill\Console\Application;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Generator;
use Mill\Parser\Version;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate command for an error map off documented API errors.
 *
 */
class ErrorMap extends Application
{
    /**
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('errors')
            ->setDescription('Compiles an error map from your documented API errors.')
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
            ->addOption(
                'private',
                null,
                InputOption::VALUE_OPTIONAL,
                'Flag designating if you want to generate an error map that includes private error documentation.',
                true
            )
            ->addOption(
                'capability',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The name of a capability if you want to generate an error map that includes capability-locked error' .
                    'documentation.'
            )
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Directory to output your generated `errors.md` file in.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $private_docs = $input->getOption('private');
        $capabilities = $input->getOption('capability');
        $output_dir = realpath($input->getArgument('output'));
        $version = $input->getOption('constraint');
        $dry_run = $input->getOption('dry-run');

        $private_docs = ($private_docs === true || strtolower($private_docs) == 'true') ? true : false;
        $capabilities = (!empty($capabilities)) ? $capabilities : null;

        // Generate!
        if ($dry_run) {
            $output->writeln('<info>Running a dry run…</info>');
        }

        if ($input->getOption('default')) {
            $version = $this->container['config']->getDefaultApiVersion();
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

        /** @var Config $config */
        $config = $this->container['config'];

        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = $this->container['filesystem'];

        $output->writeln('<comment>Generating an error map…</comment>');

        $error_map = new Generator\ErrorMap($config, $version);
        $error_map->setLoadPrivateDocs($private_docs);
        $error_map->setLoadCapabilityDocs($capabilities);
        $markdown = $error_map->generateMarkdown();

        foreach ($markdown as $version => $content) {
            $output->writeLn('<comment> - API version: ' . $version . '</comment>');

            if (!$dry_run) {
                $filesystem->put(
                    $output_dir . self::DS . $version . self::DS . 'errors.md',
                    trim($content)
                );
            }
        }

        $output->writeln(['', '<success>Done!</success>']);
    }
}
