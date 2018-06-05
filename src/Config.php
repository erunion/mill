<?php
namespace Mill;

use Composer\Semver\Semver;
use DomainException;
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
     * The base directory for this configuration file.
     *
     * @var string
     */
    protected $base_dir;

    /**
     * The name of your API.
     *
     * @var null|string
     */
    protected $name = null;

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
     * Allowable list of valid application vendor tags.
     *
     * @var array
     */
    protected $vendor_tags = [];

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
     * Array of path param translations. (Like translating `+clip_id` to `+video_id`.)
     *
     * @var array
     */
    protected $path_param_translations = [];

    /**
     * Array of `@api-param` configured replacement tokens.
     *
     * @var array
     */
    protected $parameter_tokens = [];

    /**
     * Array of API Blueprint generator resource group excludes.
     *
     * @var array
     */
    protected $blueprint_group_excludes = [];

    /**
     * Create a new configuration object from a given config file.
     *
     * @psalm-suppress UnresolvableInclude Config includes are dynamic and can't be resolved.
     * @param Filesystem $filesystem
     * @param string $config_file
     * @param bool $load_bootstrap
     * @return self
     * @throws InvalidArgumentException If the config file can't be read.
     * @throws InvalidArgumentException If the config file does not exist.
     * @throws ValidationException If there are errors validating the schema of your config file.
     */
    public static function loadFromXML(Filesystem $filesystem, string $config_file, bool $load_bootstrap = true): self
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

        if ($xml['name']) {
            $config->name = (string) $xml['name'];
        }

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

        if (isset($xml->vendorTags)) {
            $config->vendor_tags = [];
            $config->loadVendorTags($xml->vendorTags->vendorTag);
        }

        if (isset($xml->scopes)) {
            $config->scopes = [];
            $config->loadScopes($xml->scopes->scope);
        }

        if (isset($xml->pathParams) &&
            isset($xml->pathParams->translations)) {
            $config->path_param_translations = [];
            $config->loadPathParamTranslations($xml->pathParams->translations->translation);
        }

        if (isset($xml->parameterTokens)) {
            $config->loadParameterTokens($xml->parameterTokens->token);
        }

        if (isset($xml->generators)) {
            $config->loadGeneratorSettings($xml->generators);
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
     * Load an array of application vendor tags into the configuration system.
     *
     * @param SimpleXMLElement $vendor_tags
     */
    protected function loadVendorTags(SimpleXMLElement $vendor_tags): void
    {
        /** @var SimpleXMLElement $vendor_tag */
        foreach ($vendor_tags as $vendor_tag) {
            $this->addVendorTag((string) $vendor_tag['name']);
        }

        $this->vendor_tags = array_unique($this->vendor_tags);
    }

    /**
     * Add a new application vendor tag into the instance config.
     *
     * @param string $vendor_tag
     */
    public function addVendorTag(string $vendor_tag): void
    {
        $this->vendor_tags[] = $vendor_tag;
    }

    /**
     * Load an array of authentication scopes into the configuration system.
     *
     * @param SimpleXMLElement $scopes
     */
    protected function loadScopes(SimpleXMLElement $scopes): void
    {
        /** @var SimpleXMLElement $scope */
        foreach ($scopes as $scope) {
            $this->scopes[] = (string) $scope['name'];
        }

        $this->scopes = array_unique($this->scopes);
    }

    /**
     * Load an array of path param translations into the configuration system.
     *
     * @param SimpleXMLElement $translations
     */
    protected function loadPathParamTranslations($translations): void
    {
        /** @var SimpleXMLElement $translation */
        foreach ($translations as $translation) {
            $translate_from = trim((string) $translation['from']);
            $translate_to = trim((string) $translation['to']);

            $this->addPathParamTranslation($translate_from, $translate_to);
        }
    }

    /**
     * Add a new path param translation into the instance config.
     *
     * @param string $from
     * @param string $to
     * @throws DomainException If an invalid pathParam translation text was found.
     */
    public function addPathParamTranslation($from, $to): void
    {
        if (empty($from) || empty($to)) {
            throw new DomainException(
                'An invalid translation text was supplied in the Mill `pathParams` `translations` section.'
            );
        }

        $this->path_param_translations[$from] = $to;
    }

    /**
     * Load an array of `@api-param` replacement tokens into the configuration system.
     *
     * @param SimpleXMLElement $tokens
     */
    protected function loadParameterTokens(SimpleXMLElement $tokens): void
    {
        /** @var SimpleXMLElement $token */
        foreach ($tokens as $token) {
            $parameter = trim((string) $token['name']);
            $annotation = trim((string) $token);

            $this->addParameterToken($parameter, $annotation);
        }

        $this->parameter_tokens = array_unique($this->parameter_tokens);
    }

    /**
     * Add a new `@api-param` replacement token into the instance config.
     *
     * @param string $parameter
     * @param string $annotation
     * @throws DomainException If an invalid parameterTokens token name was found.
     */
    public function addParameterToken(string $parameter, string $annotation): void
    {
        if (empty($parameter) || empty($annotation)) {
            throw new DomainException(
                'An invalid parameter token name was supplied in the Mill `parameterTokens` section.'
            );
        }

        $this->parameter_tokens['{' . $parameter . '}'] = $annotation;
    }

    /**
     * Load in generator settings.
     *
     * @param SimpleXMLElement $generators
     */
    protected function loadGeneratorSettings(SimpleXMLElement $generators): void
    {
        if (isset($generators->blueprint)) {
            if (isset($generators->blueprint->excludes)) {
                /** @var SimpleXMLElement $exclude */
                foreach ($generators->blueprint->excludes->exclude as $exclude) {
                    $group = trim((string) $exclude['group']);

                    $this->addBlueprintGroupExclude($group);
                }
            }
        }
    }

    /**
     * Add a new API Blueprint resource group generator exclusion.
     *
     * @param string $group
     * @throws DomainException If an invalid Blueprint generator group exclude was detected.
     */
    public function addBlueprintGroupExclude(string $group): void
    {
        if (empty($group)) {
            throw new DomainException(
                'An invalid Blueprint generator group exclude was supplied in the Mill `generators` section.'
            );
        }

        $this->blueprint_group_excludes[] = $group;
    }

    /**
     * Remove a currently configured API Blueprint resource group generator exclusion.
     *
     * @param string $group
     */
    public function removeBlueprintGroupExclude(string $group): void
    {
        $excludes = array_flip($this->blueprint_group_excludes);
        if (isset($excludes[$group])) {
            unset($excludes[$group]);
        }

        $this->blueprint_group_excludes = array_flip($excludes);
    }

    /**
     * Load in a versions configuration definition.
     *
     * @param SimpleXMLElement $versions
     * @throws InvalidArgumentException If multiple configured default API versions were detected.
     * @throws DomainException If no default API version was set.
     */
    protected function loadVersions(SimpleXMLElement $versions): void
    {
        $api_versions = [];
        foreach ($versions as $version) {
            $version_number = (string) $version['name'];
            $description = trim((string) $version);

            $api_versions[$version_number] = [
                'version' => $version_number,
                'release_date' => (string) $version['releaseDate'],
                'description' => (!empty($description)) ? $description : null
            ];

            $is_default = (bool) $version['default'];
            if ($is_default) {
                if (!empty($this->default_api_version)) {
                    throw new InvalidArgumentException(
                        'Multiple default API versions have been detected in the Mill `versions` section.'
                    );
                }

                $this->default_api_version = (string) $version['name'];
            }
        }

        if (empty($this->default_api_version)) {
            throw new DomainException('You must set a default API version.');
        }

        $sorted_numerical = Semver::sort(array_keys($api_versions));
        foreach ($sorted_numerical as $version) {
            $this->api_versions[] = $api_versions[$version];
        }

        $this->first_api_version = current($this->api_versions)['version'];
        $this->latest_api_version = end($this->api_versions)['version'];
    }

    /**
     * Load in a controllers configuration definition.
     *
     * @param SimpleXMLElement $controllers
     * @throws InvalidArgumentException If a directory configured does not exist.
     * @throws InvalidArgumentException If no controllers were detected.
     */
    protected function loadControllers(SimpleXMLElement $controllers): void
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

            $excludes = array_unique($excludes);
        }

        /** @var SimpleXMLElement $class */
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
     * @throws InvalidArgumentException If a class could not be found.
     */
    public function addController(string $class): void
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
     * @throws UncallableRepresentationException If a configured representation does not exist.
     * @throws DomainException If a representation is configured without a `method` attribute.
     * @throws InvalidArgumentException If a directory configured does not exist.
     * @throws InvalidArgumentException If no representations were detected.
     */
    protected function loadRepresentations(SimpleXMLElement $representations): void
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

            $this->excluded_representations = array_unique($this->excluded_representations);
        }

        /** @var SimpleXMLElement $class */
        foreach ($filters->class as $class) {
            $class_name = (string) $class['name'];
            $method = (string) $class['method'] ?: null;
            if (empty($method)) {
                throw new DomainException(
                    sprintf(
                        'The `%s` representation class declaration is missing a `method` attribute.',
                        $class_name
                    )
                );
            }

            $this->addRepresentation($class_name, $method);
        }

        /** @var SimpleXMLElement $directory */
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

        ksort($this->representations);

        if (empty($this->representations)) {
            throw new InvalidArgumentException('Mill requires a set of representations to parse for documentation.');
        }
    }

    /**
     * Add a representation into the excluded list of representations.
     *
     * @param string $class
     */
    public function addExcludedRepresentation(string $class): void
    {
        $this->excluded_representations[] = $class;
    }

    /**
     * Remove a representation that has been set up to be excluded from compilation, from being excluded.
     *
     * @param string $class
     */
    public function removeExcludedRepresentation(string $class): void
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
     * @param null|string $method
     * @throws UncallableRepresentationException If the representation is uncallable.
     */
    public function addRepresentation(string $class, string $method = null): void
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
     * @throws UncallableErrorRepresentationException If a configured error representation class does not exist.
     * @throws DomainException If an error representation is missing a `method` attribute.
     */
    protected function loadErrorRepresentations(SimpleXMLElement $representations): void
    {
        /** @var SimpleXMLElement $errors */
        $errors = $representations->errors;

        /** @var SimpleXMLElement $class */
        foreach ($errors->class as $class) {
            $class_name = (string) $class['name'];
            $method = (string) $class['method'] ?: null;
            $needs_error_code = (string) $class['needsErrorCode'];

            if (!class_exists($class_name)) {
                throw UncallableErrorRepresentationException::create($class_name);
            } elseif (empty($method)) {
                throw new DomainException(
                    sprintf(
                        'The `%s` error representation class declaration is missing a `method` attribute.',
                        $class_name
                    )
                );
            }

            $this->error_representations[$class_name] = [
                'class' => $class_name,
                'method' => $method,
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
    public function isRepresentationExcluded(string $class): bool
    {
        return in_array($class, $this->getExcludedRepresentations());
    }

    /**
     * Get the name of your API.
     *
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the configured first (since) API version.
     *
     * @return string
     */
    public function getFirstApiVersion(): string
    {
        return $this->first_api_version;
    }

    /**
     * Get the configured default API version.
     *
     * @return string
     */
    public function getDefaultApiVersion(): string
    {
        return $this->default_api_version;
    }

    /**
     * Get the configured latest API version.
     *
     * @return string
     */
    public function getLatestApiVersion(): string
    {
        return $this->latest_api_version;
    }

    /**
     * Get the array of configured API versions.
     *
     * @return array
     */
    public function getApiVersions(): array
    {
        return $this->api_versions;
    }

    /**
     * Get the configured dataset (release date, description, etc) on a specific API version.
     *
     * @param string $version
     * @return array
     * @throws \Exception If a supplied version was not configured.
     */
    public function getApiVersion(string $version): array
    {
        foreach ($this->api_versions as $data) {
            if ($data['version'] == $version) {
                return $data;
            }
        }

        throw new \Exception('The supplied version, `' . $version . '`` was not found to be configured.');
    }

    /**
     * Get the array of configured application vendor tags.
     *
     * @return array
     */
    public function getVendorTags(): array
    {
        return $this->vendor_tags;
    }

    /**
     * Get the array of configured application authentication scopes.
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Get the array of configured path param translations.
     *
     * @return array
     */
    public function getPathParamTranslations(): array
    {
        return $this->path_param_translations;
    }

    /**
     * Get the array of configured `@api-param` replacement tokens.
     *
     * @return array
     */
    public function getParameterTokens(): array
    {
        return $this->parameter_tokens;
    }

    /**
     * Get the array of configured application controllers.
     *
     * @return array
     */
    public function getControllers(): array
    {
        return $this->controllers;
    }

    /**
     * Get the array of configured application representations.
     *
     * @return array
     */
    public function getRepresentations(): array
    {
        return $this->representations;
    }

    /**
     * Get the array of configured application error representations.
     *
     * @return array
     */
    public function getErrorRepresentations(): array
    {
        return $this->error_representations;
    }

    /**
     * Get an array of all configured representations (normal and error).
     *
     * @return array
     */
    public function getAllRepresentations(): array
    {
        return array_merge($this->getRepresentations(), $this->getErrorRepresentations());
    }

    /**
     * Get the array of configured excluded application representations.
     *
     * @return array
     */
    public function getExcludedRepresentations(): array
    {
        return $this->excluded_representations;
    }

    /**
     * Get the array of configured API Blueprint resource group excludes.
     *
     * @return array
     */
    public function getBlueprintGroupExcludes(): array
    {
        return $this->blueprint_group_excludes;
    }

    /**
     * Recursively scan a directory for a set of classes, excluding any that we don't want along the way.
     *
     * @param string $directory
     * @param string $suffix
     * @param array $excludes
     * @return array
     */
    protected function scanDirectoryForClasses(string $directory, string $suffix, array $excludes = []): array
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
    public function doesRepresentationExist(string $class): bool
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
    public function doesErrorRepresentationExist(string $class): bool
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
     * @return bool
     */
    public function doesErrorRepresentationNeedAnErrorCode(string $representation): bool
    {
        $representations = $this->getErrorRepresentations();
        return $representations[$representation]['needs_error_code'];
    }

    /**
     * Tokenize a given file and return back the FQN of the class inside.
     *
     * @link http://stackoverflow.com/a/7153391/105698
     * @psalm-suppress MixedArrayAccess
     * @codeCoverageIgnore
     * @param string $file
     * @return string
     */
    private function getClassFQNFromFile(string $file): string
    {
        /** @var resource $fp */
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
