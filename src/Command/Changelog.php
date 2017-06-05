<?php
namespace Mill\Command;

use Mill\Config;
use Mill\Container;
use Mill\Exceptions\Version\UnrecognizedSchemaException;
use Mill\Generator;
use Mill\Generator\Blueprint;
use Mill\Parser\Annotation;
use Mill\Parser\Representation\Documentation;
use Mill\Parser\Version;
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
     * Compiled changelog.
     *
     * @var array
     */
    protected $changelog = [];

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
        $versions = $config->getApiVersions();

        $generator = new Generator($config);
        $parsed = $generator->generate();

        $this->buildRepresentationChangelog($config, $versions, $parsed['representations']);

        //print_r($this->changelog);
    }

    /**
     * Compile a changelog for a parsed set of representations.
     *
     * @param Config $config
     * @param array $versions
     * @param array $parsed
     * @return void
     */
    private function buildRepresentationChangelog(Config $config, array $versions = [], array $parsed = [])
    {
        foreach ($parsed as $version => $representations) {
            /** @var Documentation $representation */
            foreach ($representations as $representation) {
                $content = $representation->getRawContent();

                /** @var Annotation $data */
                foreach ($content as $field => $data) {
                    // Is this data versioned?
                    $data_version = $data->getVersion();
                    if (!$data_version) {
                        continue;
                    }

                    $available_in = [];

                    // What versions is this available on?
                    foreach ($versions as $version) {
                        if ($data_version->matches($version)) {
                            $available_in[] = $version;
                        }
                    }

                    // What is the first version that this existed in?
                    $introduced = $available_in[0];
                    if ($introduced === $config->getFirstApiVersion()) {
                        continue;
                    }

                    $this->changelog[$introduced]['added']['representations'][$representation->getLabel()] = $field;

                    // What is the last version that this was available in?
                    $recent_version = end($available_in);
                    if ($recent_version === $config->getLatestApiVersion()) {
                        continue;
                    }

                    $recent_version_key = array_flip($versions)[$recent_version];
                    $removed = $versions[++$recent_version_key];
                    if ($removed !== $config->getLatestApiVersion()) {
                        continue;
                    }

                    $this->changelog[$removed]['removed']['representations'][$representation->getLabel()] = $field;
                }
            }
        }
    }
}
