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
    const SUPPORTED_AUTH_FLOWS = [
        'bearer',
        'oauth2'
    ];

    /** @var string The base directory for this configuration file. */
    protected $base_dir;

    /** @var null|string The name of your API. */
    protected $name = null;

    /** @var null|string The terms of service URL for your API. */
    protected $terms = null;

    /** @var array The contact information for your API. */
    protected $contact = [];

    /** @var array External API documentation. */
    protected $external_documentation = [];

    /** @var array Your API servers. */
    protected $servers = [];

    /** @var array Your API authentication flows. */
    protected $authentication_flows = [];

    /** @var string The first version of your API. */
    public $first_api_version;

    /** @var string The default version for your API. */
    public $default_api_version;

    /** @var string The latest version of your API. */
    public $latest_api_version;

    /** @var array Array of API versions. */
    protected $api_versions = [];

    /** @var array Allowable list of valid application vendor tags. */
    protected $vendor_tags = [];

    /** @var array Allowable list of valid application authentication scopes. */
    protected $scopes = [];

    /** @var array Array of application controllers. */
    protected $controllers = [];

    /** @var array Array of application representations. */
    protected $representations = [];

    /** @var array Array of application error representations. */
    protected $error_representations = [];

    /** @var array Array of excluded application representations. */
    protected $excluded_representations = [];

    /** @var array Array of path parameter translations. (Like translating `+clip_id` to `+video_id`.) */
    protected $path_param_translations = [];

    /** @var array Array of `@api-param` configured replacement tokens. */
    protected $parameter_tokens = [];

    /** @var array Array of compiler resource group exclusions. */
    protected $compiler_group_exclusions = [];

    /**
     * Create a new configuration object from a given config file.
     *
     * @psalm-suppress UnresolvableInclude Config includes are dynamic and can't be resolved.
     * @param Filesystem $filesystem
     * @param string $config_file
     * @param bool $load_bootstrap
     * @return Config
     * @throws UncallableErrorRepresentationException
     * @throws UncallableRepresentationException
     * @throws ValidationException
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

        $config->loadAuthentication($xml);
        $config->loadCompilerSettings($xml);
        $config->loadControllers($xml);
        $config->loadErrorRepresentations($xml);
        $config->loadInfo($xml);
        $config->loadParameterTokens($xml);
        $config->loadPathParamTranslations($xml);
        $config->loadRepresentations($xml);
        $config->loadServers($xml);
        $config->loadVendorTags($xml);
        $config->loadVersions($xml);

        return $config;
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function loadVendorTags(SimpleXMLElement $xml): void
    {
        if (!isset($xml->vendorTags)) {
            return;
        }

        /** @var SimpleXMLElement $vendor_tag */
        foreach ($xml->vendorTags->vendorTag as $vendor_tag) {
            $this->vendor_tags[] = (string) $vendor_tag['name'];
        }

        $this->vendor_tags = array_unique($this->vendor_tags);
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function loadAuthentication(SimpleXMLElement $xml): void
    {
        /** @var SimpleXMLElement $authentication */
        $authentication = $xml->authentication;

        if (isset($authentication->flows->bearer)) {
            $this->authentication_flows['bearer'] = [
                'format' => (isset($authentication->flows->bearer['format']))
                    ? (string) $authentication->flows->bearer['format']
                    : 'bearer'
            ];
        }

        if (isset($authentication->flows->oauth2)) {
            $this->authentication_flows['oauth2'] = [];

            if (isset($authentication->flows->oauth2->authorizationCode)) {
                $this->authentication_flows['oauth2']['authorization_code'] = [
                    'authorization_url' => (string) $authentication->flows->oauth2->authorizationCode['url'],
                    'token_url' => (string) $authentication->flows->oauth2->authorizationCode['tokenUrl']
                ];
            }

            if (isset($authentication->flows->oauth2->clientCredentials)) {
                $this->authentication_flows['oauth2']['client_credentials'] = [
                    'token_url' => (string) $authentication->flows->oauth2->clientCredentials['url']
                ];
            }
        }

        if (isset($authentication->scopes)) {
            /** @var SimpleXMLElement $scope */
            foreach ($authentication->scopes->scope as $scope) {
                $name = (string) $scope['name'];
                $this->scopes[$name] = [
                    'name' => $name,
                    'description' => (string) $scope['description']
                ];
            }

            ksort($this->scopes);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function loadPathParamTranslations(SimpleXMLElement $xml): void
    {
        if (!isset($xml->pathParams)) {
            return;
        } elseif (!isset($xml->pathParams->translations)) {
            return;
        }

        /** @var SimpleXMLElement $translation */
        foreach ($xml->pathParams->translations->translation as $translation) {
            $translate_from = trim((string) $translation['from']);
            $translate_to = trim((string) $translation['to']);

            $this->addPathParamTranslation($translate_from, $translate_to);
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function loadParameterTokens(SimpleXMLElement $xml): void
    {
        if (!isset($xml->parameterTokens)) {
            return;
        }

        /** @var SimpleXMLElement $token */
        foreach ($xml->parameterTokens->token as $token) {
            $parameter = trim((string) $token['name']);
            $annotation = trim((string) $token);

            $this->addParameterToken($parameter, $annotation);
        }

        $this->parameter_tokens = array_unique($this->parameter_tokens);
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function loadCompilerSettings(SimpleXMLElement $xml): void
    {
        if (!isset($xml->compilers)) {
            return;
        }

        if (isset($xml->compilers->excludes)) {
            /** @var SimpleXMLElement $exclude */
            foreach ($xml->compilers->excludes->exclude as $exclude) {
                $group = trim((string) $exclude['group']);

                $this->addCompilerGroupExclusion($group);
            }
        }
    }

    /**
     * Load in an API information configuration definition.
     *
     * @param SimpleXMLElement $xml
     */
    protected function loadInfo(SimpleXMLElement $xml): void
    {
        if (isset($xml->info->terms)) {
            $this->terms = (string) $xml->info->terms['url'];
        }

        foreach (['name', 'email', 'url'] as $contact_data) {
            if (isset($xml->info->contact[$contact_data])) {
                $this->contact[$contact_data] = (string) $xml->info->contact[$contact_data];
            }
        }

        if (isset($xml->info->externalDocs)) {
            foreach ($xml->info->externalDocs->externalDoc as $external_doc) {
                $this->external_documentation[] = [
                    'name' => (string) $external_doc['name'],
                    'url' => (string) $external_doc['url']
                ];
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml
     */
    protected function loadServers(SimpleXMLElement $xml): void
    {
        foreach ($xml->servers->server as $server) {
            $this->servers[] = [
                'environment' => strtolower((string) $server['environment']),
                'url' => (string) $server['url'],
                'description' => (string) $server['description']
            ];
        }
    }

    /**
     * @param SimpleXMLElement $xml
     * @throws InvalidArgumentException If multiple configured default API versions were detected.
     * @throws DomainException If no default API version was set.
     */
    protected function loadVersions(SimpleXMLElement $xml): void
    {
        $api_versions = [];
        foreach ($xml->versions->version as $version) {
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
     * @param SimpleXMLElement $xml
     * @throws InvalidArgumentException If a directory configured does not exist.
     * @throws InvalidArgumentException If no controllers were detected.
     */
    protected function loadControllers(SimpleXMLElement $xml): void
    {
        /** @var SimpleXMLElement $controllers */
        $controllers = $xml->controllers->filter;

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
     * @param SimpleXMLElement $xml
     * @throws UncallableRepresentationException If a configured representation does not exist.
     * @throws DomainException If a representation is configured without a `method` attribute.
     * @throws InvalidArgumentException If a directory configured does not exist.
     * @throws InvalidArgumentException If no representations were detected.
     */
    protected function loadRepresentations(SimpleXMLElement $xml): void
    {
        /** @var SimpleXMLElement $filters */
        $filters = $xml->representations->filter;

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
     * @param SimpleXMLElement $xml
     * @throws UncallableErrorRepresentationException If a configured error representation class does not exist.
     * @throws DomainException If an error representation is missing a `method` attribute.
     */
    protected function loadErrorRepresentations(SimpleXMLElement $xml): void
    {
        if (!isset($xml->representations->errors)) {
            return;
        }

        /** @var SimpleXMLElement $class */
        foreach ($xml->representations->errors->class as $class) {
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
     * Get the name of your API.
     *
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the terms of service URL for your API.
     *
     * @return null|string
     */
    public function getTerms(): ?string
    {
        return $this->terms;
    }

    /**
     * Get the contact information for your API.
     *
     * @return array
     */
    public function getContactInformation(): array
    {
        return $this->contact;
    }

    /**
     * Get external documentation for your API.
     *
     * @return array
     */
    public function getExternalDocumentation(): array
    {
        return $this->external_documentation;
    }

    /**
     * Get your API servers.
     *
     * @return array
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * Does a server environment exist in the config?
     *
     * @param string $environment
     * @return bool
     */
    public function hasServerEnvironment(string $environment): bool
    {
        return !empty($this->getServerEnvironment($environment));
    }

    /**
     * Return a configured server by its defined environment name.
     *
     * @param string $environment
     * @return bool|array
     */
    public function getServerEnvironment(string $environment)
    {
        $environment = strtolower($environment);
        foreach ($this->servers as $server) {
            if ($server['environment'] === $environment) {
                return $server;
            }
        }

        return false;
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
     * Get your API authentication flows.
     *
     * @return array
     */
    public function getAuthenticationFlows(): array
    {
        return $this->authentication_flows;
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
     * Determine if a given scope has been configured.
     *
     * @param string $scope
     * @return bool
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, array_keys($this->scopes));
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
     * Get the array of configured `@api-param` replacement tokens.
     *
     * @return array
     */
    public function getParameterTokens(): array
    {
        return $this->parameter_tokens;
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
     * Get the array of configured application controllers.
     *
     * @return array
     */
    public function getControllers(): array
    {
        return $this->controllers;
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
     * Get the array of configured compiler resource group exclusions.
     *
     * @return array
     */
    public function getCompilerGroupExclusions(): array
    {
        return $this->compiler_group_exclusions;
    }

    /**
     * Add a new compiler resource group exclusion.
     *
     * @param string $group
     * @throws DomainException If an invalid compiler group exclusion was detected.
     */
    public function addCompilerGroupExclusion(string $group): void
    {
        if (empty($group)) {
            throw new DomainException(
                'An invalid compiler group exclusion was supplied in the Mill `compilers` section.'
            );
        }

        $this->compiler_group_exclusions[] = $group;
    }

    /**
     * Remove a currently configured compiler resource group exclusion.
     *
     * @param string $group
     */
    public function removeCompilerGroupExclusion(string $group): void
    {
        $excludes = array_flip($this->compiler_group_exclusions);
        if (isset($excludes[$group])) {
            unset($excludes[$group]);
        }

        $this->compiler_group_exclusions = array_flip($excludes);
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

            $buffer .= fread($fp, 512 * 2);

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

            if (!empty($namespace) && !empty($class)) {
                break;
            }
        }

        fclose($fp);

        return implode('\\', [$namespace, $class]);
    }
}
