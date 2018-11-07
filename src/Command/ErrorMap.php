<?php
namespace Mill\Command;

use Mill\Compiler;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Parser\Version;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ErrorMap extends \Mill\Command
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
                'Compile just the configured default API version documentation. `defaultApiVersion` in your ' .
                    '`mill.xml` file.',
                false
            )
            ->addOption(
                'private',
                null,
                InputOption::VALUE_OPTIONAL,
                'Flag designating if you want to compile an error map that includes private error documentation.',
                true
            )
            ->addOption(
                'vendor_tag',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The name of a vendor tag if you want to compile an error map that includes vendor tag-bound error' .
                    'documentation.'
            )
            ->addArgument('output', InputArgument::REQUIRED, 'Directory to output your compiled `errors.md` file in.');
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

        $version = $input->getOption('constraint');

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

        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = $this->container['filesystem'];

        $output->writeln('<comment>Compiling an error map...</comment>');

        $error_map = new Compiler\ErrorMap($this->app, $version);
        $error_map->setLoadPrivateDocs($private_docs);
        $error_map->setLoadVendorTagDocs($vendor_tags);
        $markdown = $error_map->toMarkdown();

        foreach ($markdown as $version => $content) {
            $output->writeLn('<comment> - API version: ' . $version . '</comment>');

            $filesystem->put(
                $output_dir . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR . 'errors.md',
                trim($content)
            );
        }

        $output->writeln(['', '<success>Done!</success>']);

        return 0;
    }
}
