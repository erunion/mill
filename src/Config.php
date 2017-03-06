<?php
namespace Mill;

use Composer\Semver\Semver;
use DOMDocument;
use InvalidArgumentException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Mill\Exceptions\Config\UncallableErrorRepresentationException;
use Mill\Exceptions\Config\UncallableRepresentationException;
use Mill\Exceptions\Config\UnconfiguredErrorRepresentationException;
use Mill\Exceptions\Config\UnconfiguredRepresentationException;
use Mill\Exceptions\Config\ValidationException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SimpleXMLElement;

class Config
{
    /**
     * The base directory for this configuration file.e
     *
     * @var string
     */
    protected $base_dir;

    /**
     * The first version of your API.
     *
     * @var string
     */
    public $first_api_version;

    /**
     * The default version for your API.
     *
     * @var string
     */
    public $default_api_version;

    /**
     * The latest version of your API.
     *
     * @var string
     */
    public $latest_api_version;

    /**
     * Array of API versions.
     *
     * @var array
     */
    protected $api_versions = [];

    /**
     * Allowable list of valid application capabilities.
     *
     * @var array
     */
    protected $capabilities = [];

    /**
     * Allowable list of valid application authentication scopes.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Array of application controllers.
     *
     * @var array
     */
    protected $controllers = [];

    /**
     * Array of application representations.
     *
     * @var array
     */
    protected $representations = [];

    /**
     * Array of application error representations.
     *
     * @var array
     */
    protected $error_representations = [];

    /**
     * Array of excluded application representations.
     *
     * These are representations that you have, but, for whatever reason, don't want to be parsed for documentation.
     *
     * @var array
     */
    protected $excluded_representations = [];

    /**
     * Array of URI segment translations. (Like translating `+clip_id` to `+video_id`.)
     *
     * @var array
     */
    protected $uri_segment_translations = [];

    /**
     * Array of `@api-param` configured replacement tokens.
     *
     * @var array
     */
    protected $parameter_tokens = [];

    /**
     * Create a new configuration object from a given config file.
     *
     * @param Filesystem $filesystem
     * @param string $config_file
     * @param boolean $load_bootstrap
     * @return Config
     * @throws InvalidArgumentException If the config file can't be read.
     * @throws InvalidArgumentException If the config file does not exist.
     * @throws ValidationException If there are errors validating the schema of your config file.
     */
    public static function loadFromXML(Filesystem $filesystem, $config_file, $load_bootstrap = true)
    {
        $config = new self();
        $config->base_dir = dirname($config_file) . '/';

        $schema_path = dirname(__DIR__) . '/config.xsd';

        try {
            $contents = $filesystem->read($config_file);
            if (empty($contents)) {
                throw new InvalidArgumentException('The supplied Mill configuration file is invalid.');
            }
        } catch (FileNotFoundException $e) {
            throw new InvalidArgumentException('The supplied Mill configuration file does not exist.');
        }

        $dom_document = new DOMDocument;
        $dom_document->loadXML($contents);

        // Enable user error handling
        libxml_use_internal_errors(true);

        $validated = $dom_document->schemaValidate($schema_path);
        if (!$validated) {
            $errors = libxml_get_errors();
            $error = array_shift($errors);
            throw ValidationException::create($error->line, $error->message);
        }

        $xml = new SimpleXMLElement($contents);

        // Load in the user-configured bootstrap. This should either be a Composer autoload file, or set up the
        // applications autoloader so we have access to their application classes for controller and representation
        // lookups and parsing.
        //
        // Since this library is designed to be used in two cases (CLI and programmatically), if you use it
        // programmatically in your application, it'll automatically have access to your autoloader, and this'll be
        // unnecessary.
        if ($load_bootstrap) {
            require_once $config->base_dir . $xml['bootstrap'];
        }

        if (isset($xml->capabilities)) {
            $config->capabilities = [];
            $config->loadCapabilities($xml->capabilities->capability);
        }

        if (isset($xml->scopes)) {
            $config->scopes = [];
            $config->loadScopes($xml->scopes->scope);
        }

        if (isset($xml->uriSegments) &&
            isset($xml->uriSegments->translations)) {
            $config->uri_segment_translations = [];
            $config->loadUriSegmentTranslations($xml->uriSegments->translations->translation);
        }

        if (isset($xml->parameterTokens)) {
            $config->loadParameterTokens($xml->parameterTokens->token);
        }

        $config->api_versions = [];
        $config->loadVersions($xml->versions->version);

        $config->controllers = [];
        $config->loadControllers($xml->controllers);

        $config->representations = [];
        $config->error_representations = [];
        $config->excluded_representations = [];
        $config->loadRepresentations($xml->representations);
        $config->loadErrorRepresentations($xml->representations);

        return $config;
    }

    /**
     * Load an array of application capabilities into the configuration system.
     *
     * @param array $capabilities
     * @return void
     */
    protected function loadCapabilities($capabilities = [])
    {
        foreach ($capabilities as $capability) {
            $this->capabilities[] = (string) $capability['name'];
        }

        // Keep things tidy.
        $this->capabilities = array_unique($this->capabilities);
    }

    /**
     * Load an array of authentication scopes into the configuration system.
     *
     * @param array $scopes
     * @return void
     */
    protected function loadScopes($scopes = [])
    {
        foreach ($scopes as $scope) {
            $this->scopes[] = (string) $scope['name'];
        }

        // Keep things tidy.
        $this->scopes = array_unique($this->scopes);
    }

    /**
     * Load an array of URI segment translations into the configuration system.
     *
     * @param array $translations
     * @return void
     */
    protected function loadUriSegmentTranslations($translations = [])
    {
        /** @var SimpleXMLElement $translation */
        foreach ($translations as $translation) {
            $translate_from = trim((string) $translation['from']);
            $translate_to = trim((string) $translation['to']);

            $this->addUriSegmentTranslation($translate_from, $translate_to);
        }
    }

    /**
     * Add a new URI segment translation into the instance config.
     *
     * @param string $from
     * @param string $to
     * @return void
     * @throws InvalidArgumentException If an invalid uriSegment translation text was found.
     */
    public function addUriSegmentTranslation($from, $to)
    {
        if (empty($from) || empty($to)) {
            throw new InvalidArgumentException(
                'An invalid translation text was supplied in the Mill `uriSegmentTranslations` section.'
            );
        }

        $this->uri_segment_translations[$from] = $to;
    }

    /**
     * Load an array of `@api-param` replacement tokens into the configuration system.
     *
     * @param array $tokens
     * @return void
     */
    protected function loadParameterTokens($tokens = [])
    {
        foreach ($tokens as $token) {
            $parameter = trim((string) $token['name']);
            $annotation = trim((string) $token);

            $this->addParameterToken($parameter, $annotation);
        }

        // Keep things tidy.
        $this->parameter_tokens = array_unique($this->parameter_tokens);
    }

    /**
     * Add a new `@api-param` replacement token into the instance config.
     *
     * @param string $parameter
     * @param string $annotation
     * @return void
     * @throws InvalidArgumentException If an invalid parameterTokens token name was found.
     */
    public function addParameterToken($parameter, $annotation)
    {
        if (empty($parameter) || empty($annotation)) {
            throw new InvalidArgumentException(
                'An invalid parameter token name was supplied in the Mill `parameterTokens` section.'
            );
        }

        $this->parameter_tokens['{' . $parameter . '}'] = $annotation;
    }

    /**
     * Load in a versions configuration definition.
     *
     * @param SimpleXMLElement $versions
     * @return void
     * @throws InvalidArgumentException If multiple configured default API versions were detected.
     */
    protected function loadVersions(SimpleXMLElement $versions)
    {
        foreach ($versions as $version) {
            $this->api_versions[] = (string) $version['name'];

            $is_default = (bool) $version['default'];
            if ($is_default) {
                if ($this->getDefaultApiVersion()) {
                    throw new InvalidArgumentException(
                        'Multiple default API versions have been detected in the Mill `versions` section.'
                    );
                }

                $this->default_api_version = (string) $version['name'];
            }
        }

        if (!$this->getDefaultApiVersion()) {
            throw new InvalidArgumentException('You must set a default API version.');
        }

        // Keep things tidy.
        $this->api_versions = array_unique($this->api_versions);

        $this->first_api_version = Semver::sort($this->api_versions)[0];
        $this->latest_api_version = Semver::rsort($this->api_versions)[0];
    }

    /**
     * Load in a controllers configuration definition.
     *
     * @param SimpleXMLElement $controllers
     * @return void
     * @throws InvalidArgumentException If a directory configured does not exist.
     * @throws InvalidArgumentException If no controllers were detected.
     */
    protected function loadControllers(SimpleXMLElement $controllers)
    {
        /** @var SimpleXMLElement $controllers */
        $controllers = $controllers->filter;

        $excludes = [];
        if (isset($controllers->excludes)) {
            /** @var SimpleXMLElement $exclude_config */
            $exclude_config = $controllers->excludes;

            /** @var SimpleXMLElement $exclude */
            foreach ($exclude_config->exclude as $exclude) {
                $excludes[] = (string) $exclude['name'];
            }

            // Keep things tidy.
            $excludes = array_unique($excludes);
        }

        /**
         * Process classes.
         *
         * @var SimpleXMLElement $file
         */
        foreach ($controllers->class as $class) {
            $this->addController((string) $class['name']);
        }

        /** @var SimpleXMLElement $directory */
        foreach ($controllers->directory as $directory) {
            $directory_name = (string) $this->base_dir . $directory['name'];
            if (!is_dir($directory_name)) {
                throw new InvalidArgumentException(sprintf('The `%s` directory does not exist.', $directory_name));
            }

            $suffix = (string) $directory['suffix'] ?: '.php';

            $this->controllers = array_merge(
                $this->controllers,
                $this->scanDirectoryForClasses($directory_name, $suffix, $excludes)
            );
        }

        // Keep things tidy.
        $this->controllers = array_unique($this->controllers);
        sort($this->controllers);

        if (empty($this->controllers)) {
            throw new InvalidArgumentException('Mill requires a set of controllers to parse for documentation.');
        }
    }

    /**
     * Add a new resource controller into the instance config.
     *
     * @param string $class
     * @return void
     * @throws InvalidArgumentException If a class could not be found.
     */
    public function addController($class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The `%s` controller class could not be called. Is your bootstrap set up properly?',
                    $class
                )
            );
        }

        $this->controllers[] = $class;
    }

    /**
     * Load in a representations configuration definition.
     *
     * @param SimpleXMLElement $representations
     * @return void
     * @throws UncallableRepresentationException If a configured representation does not exist.
     * @throws InvalidArgumentException If a representation is configured without a `method` attribute.
     * @throws InvalidArgumentException If a directory configured does not exist.
     * @throws InvalidArgumentException If no representations were detected.
     */
    protected function loadRepresentations(SimpleXMLElement $representations)
    {
        /** @var SimpleXMLElement $filters */
        $filters = $representations->filter;

        // Process excludes.
        if (isset($filters->excludes)) {
            /** @var SimpleXMLElement $exclude_config */
            $exclude_config = $filters->excludes;

            /** @var SimpleXMLElement $exclude */
            foreach ($exclude_config->exclude as $exclude) {
                $this->addExcludedRepresentation((string) $exclude['name']);
            }

            // Keep things tidy.
            $this->excluded_representations = array_unique($this->excluded_representations);
        }

        /**
         * Process classes.
         *
         * @var SimpleXMLElement $class
         */
        foreach ($filters->class as $class) {
            $class_name = (string) $class['name'];
            $method = (string) $class['method'] ?: null;
            if (empty($method)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The `%s` representation class declaration is missing a `method` attribute.',
                        $class_name
                    )
                );
            }

            $this->addRepresentation($class_name, $method);
        }

        /**
         * Process directories.
         *
         * @var SimpleXMLElement $directory
         */
        foreach ($filters->directory as $directory) {
            $directory_name = (string) $this->base_dir . $directory['name'];
            if (!is_dir($directory_name)) {
                throw new InvalidArgumentException(sprintf('The `%s` directory does not exist.', $directory_name));
            }

            $suffix = (string) $directory['suffix'] ?: '.php';
            $method = (string) $directory['method'] ?: null;

            $classes = $this->scanDirectoryForClasses($directory_name, $suffix, $this->excluded_representations);
            /** @var string $class */
            foreach ($classes as $class) {
                // Class declarations should always take priority over directories.
                if (isset($this->representations[$class])) {
                    continue;
                }

                $this->addRepresentation($class, $method);
            }
        }

        // Keep things tidy
        ksort($this->representations);

        if (empty($this->representations)) {
            throw new InvalidArgumentException('Mill requires a set of representations to parse for documentation.');
        }
    }

    /**
     * Add a representation into the excluded list of representations.
     *
     * @param string $class
     * @return void
     */
    public function addExcludedRepresentation($class)
    {
        $this->excluded_representations[] = $class;
    }

    /**
     * Remove a representation that has been set up to be excluded from compilation, from being excluded.
     *
     * @param string $class
     * @return void
     */
    public function removeExcludedRepresentation($class)
    {
        $excludes = array_flip($this->excluded_representations);
        if (isset($excludes[$class])) {
            unset($excludes[$class]);
        }

        $this->excluded_representations = array_flip($excludes);
    }

    /**
     * Add a new representation into the instance config.
     *
     * @param string $class
     * @param string|null $method
     * @return void
     * @throws UncallableRepresentationException If the representation is uncallable.
     */
    public function addRepresentation($class, $method = null)
    {
        if (!class_exists($class)) {
            throw UncallableRepresentationException::create($class);
        }

        $this->representations[$class] = [
            'class' => $class,
            'method' => $method
        ];
    }

    /**
     * Load in an error representations configuration definition.
     *
     * @param SimpleXMLElement $representations
     * @return void
     * @throws UncallableErrorRepresentationException If a configured error representation class does not exist.
     */
    protected function loadErrorRepresentations(SimpleXMLElement $representations)
    {
        /** @var SimpleXMLElement $errors */
        $errors = $representations->errors;

        /** @var SimpleXMLElement $class */
        foreach ($errors->class as $class) {
            $class_name = (string) $class['name'];
            $needs_error_code = (string) $class['needsErrorCode'];

            if (!class_exists($class_name)) {
                throw UncallableErrorRepresentationException::create($class_name);
            }

            $this->error_representations[$class_name] = [
                'class' => $class_name,
                'needs_error_code' => (strtolower($needs_error_code) === 'true')
            ];
        }
    }

    /**
     * Check if a given representation has been configured to be excluded.
     *
     * @param string $class
     * @return bool
     */
    public function isRepresentationExcluded($class)
    {
        return in_array($class, $this->getExcludedRepresentations());
    }

    /**
     * Get the configured first (since) API version.
     *
     * @return string
     */
    public function getFirstApiVersion()
    {
        return $this->first_api_version;
    }

    /**
     * Get the configured default API version.
     *
     * @return string
     */
    public function getDefaultApiVersion()
    {
        return $this->default_api_version;
    }

    /**
     * Get the configured latest API version.
     *
     * @return string
     */
    public function getLatestApiVersion()
    {
        return $this->latest_api_version;
    }

    /**
     * Get the array of configured API versions.
     *
     * @return array
     */
    public function getApiVersions()
    {
        return $this->api_versions;
    }

    /**
     * Get the array of configured application capabilities.
     *
     * @return array
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * Get the array of configured application authentication scopes.
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Get the array of configured URI segment translations.
     *
     * @return array
     */
    public function getUriSegmentTranslations()
    {
        return $this->uri_segment_translations;
    }

    /**
     * Get the array of configured `@api-param` replacement tokens.
     *
     * @return array
     */
    public function getParameterTokens()
    {
        return $this->parameter_tokens;
    }

    /**
     * Get the array of configured application controllers.
     *
     * @return array
     */
    public function getControllers()
    {
        return $this->controllers;
    }

    /**
     * Get the array of configured application representations.
     *
     * @return array
     */
    public function getRepresentations()
    {
        return $this->representations;
    }

    /**
     * Get the array of configured application error representations.
     *
     * @return array
     */
    public function getErrorRepresentations()
    {
        return $this->error_representations;
    }

    /**
     * Get the array of configured excluded application representations.
     *
     * @return array
     */
    public function getExcludedRepresentations()
    {
        return $this->excluded_representations;
    }

    /**
     * Recursively scan a directory for a set of classes, excluding any that we don't want along the way.
     *
     * @param string $directory
     * @param string $suffix
     * @param array $excludes
     * @return array
     */
    protected function scanDirectoryForClasses($directory, $suffix, array $excludes = [])
    {
        $classes = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $regex = new RegexIterator($iterator, '/^.+(' . $suffix . ')$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($regex as $file) {
            $file = array_shift($file);

            $class = $this->getClassFQNFromFile($file);
            if (in_array($class, $excludes)) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Check if a specific representation exists. If the representation has been configured as being excluded, then act
     * as if it doesn't exist (but don't throw an exception).
     *
     * @param string $class
     * @return bool
     * @throws UnconfiguredRepresentationException If the representation hasn't been configured, or excluded.
     */
    public function doesRepresentationExist($class)
    {
        $representations = $this->getRepresentations();
        $excluded = $this->getExcludedRepresentations();

        // If the representation is excluded, just act like it doesn't exist.
        if (in_array($class, $excluded)) {
            return false;
        }

        // However, if it isn't, but also hasn't been configured, fail out.
        if (!isset($representations[$class])) {
            throw UnconfiguredRepresentationException::create($class);
        }

        return true;
    }

    /**
     * Check if a specific error representation exists.
     *
     * @param string $class
     * @return bool
     * @throws UnconfiguredErrorRepresentationException If the error representation hasn't been configured.
     */
    public function doesErrorRepresentationExist($class)
    {
        $representations = $this->getErrorRepresentations();
        if (!isset($representations[$class])) {
            throw UnconfiguredErrorRepresentationException::create($class);
        }

        return true;
    }

    /**
     * Check if a given error representation requires an error code.
     *
     * @param string $representation
     * @return boolean
     */
    public function doesErrorRepresentationNeedAnErrorCode($representation)
    {
        $representations = $this->getErrorRepresentations();
        return $representations[$representation]['needs_error_code'];
    }

    /**
     * Tokenize a given file and return back the FQN of the class inside.
     *
     * @link http://stackoverflow.com/a/7153391/105698
     * @param string $file
     * @return string
     * @psalm-suppress MixedArrayAccess
     * @codeCoverageIgnore
     */
    private function getClassFQNFromFile($file)
    {
        $fp = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 512);

            // Hide warnings from this that might arise from unterminated docblock comments.
            $tokens = @token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i<count($tokens); $i++) {
                switch ($tokens[$i][0]) {
                    case T_NAMESPACE:
                        for ($j=$i+1; $j<count($tokens); $j++) {
                            if ($tokens[$j][0] === T_STRING) {
                                $namespace .= '\\' . $tokens[$j][1];
                            } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                break;
                            }
                        }
                        break;

                    case T_CLASS:
                        for ($j=$i+1; $j<count($tokens); $j++) {
                            if ($tokens[$j] === '{') {
                                $class = $tokens[$i+2][1];
                            }
                        }
                        break;
                }
            }
        }

        return implode('\\', [$namespace, $class]);
    }
}
