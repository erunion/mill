<?php
namespace Mill\Command;

use Mill\Config;
use Mill\Compiler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Changelog extends \Mill\Command
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
                'Flag designating if you want to compile a changelog that includes private documentation.',
                true
            )
            ->addOption(
                'vendor_tag',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The name of a vendor tag if you want to compile a changelog that includes vendor tag-bound ' .
                    'documentation.'
            )
            ->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Directory to output your compiled `changelog.md` file in.'
            );
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

        /** @var array|null $vendor_tags */
        $vendor_tags = $input->getOption('vendor_tag');
        $vendor_tags = (!empty($vendor_tags)) ? $vendor_tags : null;

        /** @var string $output_dir */
        $output_dir = $input->getArgument('output');
        $output_dir = realpath($output_dir);

        $private_docs = $input->getOption('private');
        if (is_bool($private_docs) && $private_docs === true) {
            $private_docs = true;
        } elseif (is_string($private_docs) && strtolower($private_docs) == 'true') {
            $private_docs = true;
        } else {
            $private_docs = false;
        }

        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = $this->container['filesystem'];

        $output->writeln('<comment>Compiling a changelog...</comment>');

        $changelog = new Compiler\Changelog($this->app);
        $changelog->setLoadPrivateDocs($private_docs);
        $changelog->setLoadVendorTagDocs($vendor_tags);
        $markdown = $changelog->toMarkdown();

        $filesystem->put($output_dir . DIRECTORY_SEPARATOR . 'changelog.md', trim($markdown));

        $output->writeln(['', '<success>Done!</success>']);

        return 0;
    }
}
