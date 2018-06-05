<?php
namespace Mill;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends Command
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * When building out dot-notation annotation keys for compiling documentation we use this key to designate the
     * content of an annotations' data.
     *
     * @var string
     */
    const DOT_NOTATION_ANNOTATION_DATA_KEY = '__NESTED_DATA__';

    /**
     * When building out dot-notation annotation keys for compiling documentation we use this key to designate the
     * type of parameter that it is.
     *
     * @var string
     */
    const DOT_NOTATION_ANNOTATION_PARAMETER_TYPE_KEY = '__PARAMETER_TYPE__';

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
