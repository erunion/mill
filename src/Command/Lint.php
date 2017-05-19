<?php
namespace Mill\Command;

use Mill\Container;
use Mill\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lint command for running validation checks against your Mill documentation.
 *
 */
class Lint extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('lint')
            ->setDescription('Lint your documentation for any validation issues.')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to your `mill.xml` config file.',
                'mill.xml'
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

        // @todo This should be pulled from the core Application instead, so we can inject the dependency in tests.
        $container = new Container([
            'config.path' => realpath($input->getOption('config'))
        ]);

        $output->writeln('<comment>Linting your API documentation…</comment>');
        $generator = new Generator($container['config']);
        $generator->generate();

        $output->writeln('<success>No errors found.</success>');
    }
}
