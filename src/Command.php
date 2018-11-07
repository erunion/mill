<?php
namespace Mill;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends \Symfony\Component\Console\Command\Command
{
    /** @var Application */
    protected $app;

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

        /** @var string $config_file */
        $config_file = $input->getOption('config');
        $config_file = realpath($config_file);

        $this->app = new Application($config_file);
        $this->container = $this->app->getContainer();

        return 0;
    }
}
