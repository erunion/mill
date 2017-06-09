<?php
namespace Mill\Command;

use Mill\Config;
use Mill\Container;
use Mill\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
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
            );
            /*->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Execute a dry run (do not save any files).',
                false
            )
            ->addArgument('output', InputArgument::REQUIRED, 'Directory to output your generated `.apib` files in.');*/
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

        // @todo This should be pulled from the core Application instead, so we can inject the dependency in tests.
        $container = new Container([
            'config.path' => realpath($input->getOption('config'))
        ]);

        /** @var Config $config */
        $config = $container['config'];

        $changelog = new Generator\Changelog($config);
        $changelog->generate();

//print_r($this->changelog);
    }
}
