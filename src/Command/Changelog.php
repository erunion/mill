<?php
namespace Mill\Command;

use Mill\Config;
use Mill\Console\Application;
use Mill\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate command for a changelog off your API documentation.
 *
 */
class Changelog extends Application
{
    /**
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('changelog')
            ->setDescription('Compiles a changelog from your API documentation.')
            ->addOption(
                'private',
                null,
                InputOption::VALUE_OPTIONAL,
                'Flag designating if you want to generate a changelog that includes private documentation.',
                true
            )
            ->addOption(
                'capability',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The name of a capability if you want to generate a changelog that includes capability-locked ' .
                    'documentation.'
            )
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Directory to output your generated `changelog.md` file in.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $private_docs = $input->getOption('private');
        $capabilities = $input->getOption('capability');
        $output_dir = realpath($input->getArgument('output'));

        $private_docs = ($private_docs === true || strtolower($private_docs) == 'true') ? true : false;
        $capabilities = (!empty($capabilities)) ? $capabilities : null;

        /** @var Config $config */
        $config = $this->container['config'];

        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = $this->container['filesystem'];

        $output->writeln('<comment>Generating a changelog…</comment>');

        $changelog = new Generator\Changelog($config);
        $changelog->setLoadPrivateDocs($private_docs);
        $changelog->setLoadCapabilityDocs($capabilities);
        $markdown = $changelog->generateMarkdown();

        $filesystem->put(
            $output_dir . self::DS . 'changelog.md',
            trim($markdown)
        );

        $output->writeln(['', '<success>Done!</success>']);

        return 0;
    }
}
