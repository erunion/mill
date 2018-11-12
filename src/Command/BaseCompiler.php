<?php
namespace Mill\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCompiler extends \Mill\Command
{
    /** @var string $output_dir */
    protected $output_dir;

    /** @var bool */
    protected $private_docs;

    /** @var null|array */
    protected $vendor_tags = null;

    /**
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'private',
            null,
            InputOption::VALUE_OPTIONAL,
            "Flag designating if you want to include documentation that's marked as private.",
            true
        );

        $this->addOption(
            'vendor_tag',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'The name of a vendor tag if you want to incorporate documentation that includes vendor tag-bound ' .
                'annotations documentation.'
        );

        $this->addArgument('output', InputArgument::REQUIRED, 'Directory to output into.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        /** @var array|null $vendor_tags */
        $vendor_tags = $input->getOption('vendor_tag');
        $this->vendor_tags = (!empty($vendor_tags)) ? $vendor_tags : null;

        /** @var string $output_dir */
        $output_dir = $input->getArgument('output');
        $this->output_dir = realpath($output_dir);

        $private_docs = $input->getOption('private');
        if (is_bool($private_docs) && $private_docs === true) {
            $this->private_docs = true;
        } elseif (is_string($private_docs) && strtolower($private_docs) == 'true') {
            $this->private_docs = true;
        } else {
            $this->private_docs = false;
        }

        return 0;
    }
}
