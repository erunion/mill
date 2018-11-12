<?php
namespace Mill\Command;

use Mill\Compiler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Changelog extends BaseCompiler
{
    /**
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('changelog')
            ->setDescription('Compiles a changelog from your API documentation.');
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

        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = $this->container['filesystem'];

        $output->writeln('<comment>Compiling a changelog...</comment>');

        $changelog = new Compiler\Changelog($this->app);
        $changelog->setLoadPrivateDocs($this->private_docs);
        $changelog->setLoadVendorTagDocs($this->vendor_tags);
        $markdown = $changelog->toMarkdown();

        $filesystem->put($this->output_dir . DIRECTORY_SEPARATOR . 'changelog.md', trim($markdown));

        $output->writeln(['', '<success>Done!</success>']);

        return 0;
    }
}
