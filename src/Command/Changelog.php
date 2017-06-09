<?php
namespace Mill\Command;

use Mill\Config;
use Mill\Container;
use Mill\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate command for a changelog off your API documentation.
 *
 */
class Changelog extends Command
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('changelog')
            ->setDescription('Compile a changelog off your API documentation.')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to your `mill.xml` config file.',
                'mill.xml'
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
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('green', null, ['bold']);
        $output->getFormatter()->setStyle('success', $style);

        $output_dir = realpath($input->getArgument('output'));
        $config_file = realpath($input->getOption('config'));

        // @todo This should be pulled from the core Application instead, so we can inject the dependency in tests.
        $container = new Container([
            'config.path' => $config_file
        ]);

        /** @var Config $config */
        $config = $container['config'];

        /** @var \League\Flysystem\Filesystem $filesystem */
        $filesystem = $container['filesystem'];

        $output->writeln('<comment>Generating a changelog…</comment>');

        $changelog = new Generator\Changelog($config);
        $markdown = $changelog->generateMarkdown();

        $filesystem->put(
            $output_dir . self::DS . 'changelog.md',
            trim($markdown)
        );

        $output->writeln(['', '<success>Done!</success>']);
    }
}
