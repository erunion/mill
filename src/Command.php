<?php
namespace Mill;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends \Symfony\Component\Console\Command\Command
{
    /** @var \Pimple\Container */
    protected $container;

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addOption(
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
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('green', null, ['bold']);
        $output->getFormatter()->setStyle('success', $style);

        $config_file = realpath($input->getOption('config'));

        $this->container = new Container([
            'config.path' => $config_file
        ]);

        return 0;
    }
}
