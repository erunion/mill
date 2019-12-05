<?php
namespace Mill\Command;

use Mill\Compiler;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Parser\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ErrorMap extends BaseCompiler
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

        /** @var string|null */
        $version = $input->getOption('constraint');

        if ($input->getOption('default')) {
            /** @var string|null */
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

        /** @psalm-suppress PossiblyInvalidArgument */
        $error_map = new Compiler\ErrorMap($this->app, $version);
        $error_map->setLoadPrivateDocs($this->private_docs);
        $error_map->setLoadVendorTagDocs($this->vendor_tags);
        $markdown = $error_map->toMarkdown();

        foreach ($markdown as $version => $content) {
            $output->writeLn('<comment> - API version: ' . $version . '</comment>');

            $filesystem->put(
                $this->output_dir . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR . 'errors.md',
                trim($content)
            );
        }

        $output->writeln(['', '<success>Done!</success>']);

        return 0;
    }
}
