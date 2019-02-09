<?php
namespace Mill\Tests;

use Mill\Config;
use Mill\Exceptions\Config\UncallableErrorRepresentationException;
use Mill\Exceptions\Config\UncallableRepresentationException;

class ConfigTest extends TestCase
{
    public function testLoadFromXML(): void
    {
        $config = $this->getConfig();

        $this->assertSame('Mill unit test API, Showtimes', $config->getName());
        $this->assertSame('https://example.com/terms', $config->getTerms());

        $this->assertSame([
            'name' => 'Get help!',
            'email' => 'support@example.com',
            'url' => 'https://developer.example.com/help'
        ], $config->getContactInformation());

        $this->assertSame([
            [
                'name' => 'Developer Docs',
                'url' => 'https://developer.example.com'
            ]
        ], $config->getExternalDocumentation());

        $this->assertSame([
            [
                'environment' => 'prod',
                'url' => 'https://api.example.com',
                'description' => 'Production'
            ],
            [
                'environment' => 'dev',
                'url' => 'https://api.example.local',
                'description' => 'Development'
            ]
        ], $config->getServers());

        $this->assertTrue($config->hasServerEnvironment('prod'));
        $this->assertTrue($config->hasServerEnvironment('dev'));
        $this->assertFalse($config->hasServerEnvironment('local'));

        $this->assertSame([
            'environment' => 'prod',
            'url' => 'https://api.example.com',
            'description' => 'Production'
        ], $config->getServerEnvironment('prod'));

        $this->assertSame('1.0', $config->getFirstApiVersion());
        $this->assertSame('1.1.2', $config->getDefaultApiVersion());
        $this->assertSame('1.1.3', $config->getLatestApiVersion());

        $this->assertSame([
            [
                'version' => '1.0',
                'release_date' => '2017-01-01',
                'description' => null
            ],
            [
                'version' => '1.1',
                'release_date' => '2017-02-01',
                'description' => null
            ],
            [
                'version' => '1.1.1',
                'release_date' => '2017-03-01',
                'description' => null
            ],
            [
                'version' => '1.1.2',
                'release_date' => '2017-04-01',
                'description' => null
            ],
            [
                'version' => '1.1.3',
                'release_date' => '2017-05-27',
                'description' => 'Changed up the responses for `/movie/{id}`, `/movies/{id}` and `/movies`.'
            ]
        ], $config->getApiVersions());

        $this->assertSame([
            'FakeExcludeGroup'
        ], $config->getCompilerGroupExclusions());

        $this->assertSame([
            'tag:BUY_TICKETS',
            'tag:DELETE_CONTENT',
            'tag:FEATURE_FLAG',
            'tag:MOVIE_RATINGS'
        ], $config->getVendorTags());

        $this->assertSame([
            'Movies' => 'These resources help you handle movies.',
            'Movies\Coming Soon' => null,
            'Theaters' => null
        ], $config->getTags());

        $this->assertSame([
            'bearer' => [
                'format' => 'bearer'
            ],
            'oauth2' => [
                'authorization_code' => [
                    'authorization_url' => '/oauth/authorize',
                    'token_url' => '/oauth/access_token'
                ],
                'client_credentials' => [
                    'token_url' => '/oauth/authorize/client'
                ]
            ]
        ], $config->getAuthenticationFlows());

        $this->assertSame([
            'create' => [
                'name' => 'create',
                'description' => 'Create'
            ],
            'delete' => [
                'name' => 'delete',
                'description' => 'Delete'
            ],
            'edit' => [
                'name' => 'edit',
                'description' => 'Edit'
            ],
            'public' => [
                'name' => 'public',
                'description' => 'Public'
            ]
        ], $config->getScopes());

        $this->assertSame([
            '\Mill\Examples\Showtimes\Controllers\Movie',
            '\Mill\Examples\Showtimes\Controllers\Movies',
            '\Mill\Examples\Showtimes\Controllers\Theater',
            '\Mill\Examples\Showtimes\Controllers\Theaters'
        ], $config->getControllers());

        $this->assertCount(6, $config->getAllRepresentations());
        $this->assertCount(6, $config->getRepresentations());
        $this->assertSame([
            '\Mill\Examples\Showtimes\Representations\Error' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Error',
                'method' => 'create',
                'needs_error_code' => false
            ],
            '\Mill\Examples\Showtimes\Representations\CodedError' => [
                'class' => '\Mill\Examples\Showtimes\Representations\CodedError',
                'method' => 'create',
                'needs_error_code' => true
            ]
        ], $config->getErrorRepresentations());

        $this->assertEmpty($config->getExcludedRepresentations());

        $this->assertSame([
            'movie_id' => 'id',
            'theater_id' => 'id'
        ], $config->getPathParamTranslations());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /does not exist/
     */
    public function testLoadFromXMLFailsIfConfigFileDoesNotExist(): void
    {
        $filesystem = $this->getContainer()->getFilesystem();
        Config::loadFromXML($filesystem, 'non-existent.xml');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /is invalid/
     */
    public function testLoadFromXMLFailsIfConfigFileIsInvalid(): void
    {
        $filesystem = $this->getContainer()->getFilesystem();
        $filesystem->write('empty.xml', '');

        Config::loadFromXML($filesystem, 'empty.xml');
    }

    /**
     * @expectedException \Mill\Exceptions\Config\ValidationException
     */
    public function testXSDValidation(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mill>
</mill>
XML;

        $filesystem = $this->getContainer()->getFilesystem();
        $filesystem->write('mill.bad.xml', $xml);

        try {
            Config::loadFromXML($filesystem, 'mill.bad.xml');
        } catch (\Exception $e) {
            $filesystem->delete('mill.bad.xml');
            throw $e;
        }
    }

    /**
     * @dataProvider providerLoadFromXMLFailuresOnVariousBadXMLFiles
     * @param array $exception_details
     * @param string $xml
     * @throws \Exception
     */
    public function testLoadFromXMLFailuresOnVariousBadXMLFiles(array $exception_details, string $xml): void
    {
        /** @var string $provider */
        $provider = $this->getName();

        if (isset($exception_details['exception'])) {
            $this->expectException($exception_details['exception']);
        } else {
            $this->expectException(\DomainException::class);
            $this->expectExceptionMessageRegExp($exception_details['regex']);
        }

        // Customize the provider XML so we don't need to have a bunch of DRY'd XML everywhere.
        $info = $servers = $versions = $controllers = $representations = $authentication = false;
        if (strpos($provider, 'info.') === false) {
            $info = <<<XML
<info>
    <terms url="https://example.com/terms" />

    <contact
        name="Get help!"
        email="support@example.com"
        url="https://developer.example.com/help" />

    <externalDocs>
        <externalDoc name="Developer Docs" url="https://developer.example.com" />
    </externalDocs>
</info>
XML;
        }

        if (strpos($provider, 'servers.') === false) {
            $servers = <<<XML
<servers>
    <server environment="prod" url="https://api.example.com" description="Production" />
    <server environment="dev" url="https://api.example.local" description="Development" />
</servers>
XML;
        }

        if (strpos($provider, 'versions.') === false) {
            $versions = <<<XML
<versions>
    <version name="1.0" releaseDate="2017-01-01" />
    <version name="1.1" releaseDate="2017-02-01" default="true" />
</versions>
XML;
        }

        if (strpos($provider, 'controllers.') === false) {
            $controllers = <<<XML
<controllers>
    <filter>
        <class name="\Mill\Examples\Showtimes\Controllers\Movie" />
    </filter>
</controllers>
XML;
        }

        if (strpos($provider, 'representations.') === false) {
            $representations = <<<XML
<representations>
    <filter>
        <class name="\Mill\Examples\Showtimes\Representations\Movie" method="create" />
    </filter>
</representations>
XML;
        }

        if (strpos($provider, 'authentication.') === false) {
            $authentication = <<<XML
<authentication>
    <flows>
        <bearer format="bearer" />

        <oauth2>
            <authorizationCode url="/oauth/authorize" tokenUrl="/oauth/access_token" />
            <clientCredentials url="/oauth/authorize/client" />
        </oauth2>
    </flows>

    <scopes>
        <scope name="create" description="Create" />
        <scope name="delete" description="Delete" />
        <scope name="edit" description="Edit" />
        <scope name="public" description="Public" />
    </scopes>
</authentication>
XML;
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mill name="Test API" bootstrap="vendor/autoload.php">
    $info
    $servers
    $versions
    $controllers
    $representations
    $authentication
    $xml

    <tags>
        <tag name="Movies">These resources help you handle movies.</tag>
        <tag name="Theaters" />
    </tags>
</mill>
XML;

        $filesystem = $this->getContainer()->getFilesystem();
        $filesystem->put('mill.bad.xml', $xml);

        try {
            Config::loadFromXML($filesystem, 'mill.bad.xml');
        } catch (\Exception $e) {
            $filesystem->delete('mill.bad.xml');
            throw $e;
        }
    }

    /**
     * @expectedException \Mill\Exceptions\Config\UnconfiguredRepresentationException
     */
    public function testDoesRepresentationExistFailsIfRepresentationIsNotConfigured(): void
    {
        $this->getConfig()->doesRepresentationExist('UnconfiguredClass');
    }

    /**
     * @return array
     */
    public function providerLoadFromXMLFailuresOnVariousBadXMLFiles(): array
    {
        return [
            /**
             * <versions>
             *
             */
            'versions.no-default' => [
                'exception' => [
                    'regex' => '/You must set/'
                ],
                'xml' => '
<versions>
    <version name="1.0" releaseDate="2017-01-01" />
</versions>'
            ],

            'versions.multiple-defaults' => [
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/Multiple default API versions/'
                ],
                'xml' => '
<versions>
    <version name="1.0" releaseDate="2017-01-01" default="true" />
    <version name="1.1" releaseDate="2017-02-01" default="true" />
</versions>'
            ],

            /**
             * <compilers>
             *
             */
            'compilers.exclude.invalid' => [
                'exception' => [
                    'regex' => '/invalid compiler group exclusion/'
                ],
                'xml' => '
<compilers>
    <excludes>
        <exclude group="" />
    </excludes>
</compilers>'
            ],

            /**
             * <controllers>
             *
             */
            'controllers.directory.invalid' => [
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/does not exist/'
                ],
                'xml' => '
<controllers>
    <filter>
        <directory name="invalid/directory/" />
    </filter>
</controllers>'
            ],

            'controllers.none-found' => [
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/requires a set of controllers/'
                ],
                'xml' => '
<controllers>
    <filter>
        <directory name="resources/examples/Showtimes/Controllers/" suffix=".phps" />
    </filter>
</controllers>'
            ],

            'controllers.class.uncallable' => [
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/could not be called/'
                ],
                'xml' => '
<controllers>
    <filter>
        <class name="\UncallableClass" />
    </filter>
</controllers>'
            ],

            /**
             * <representations>
             *
             */
            'representations.none-found' => [
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/requires a set of representations/'
                ],
                'xml' => '
<representations>
    <filter>
        <directory name="resources/examples/Showtimes/Representations" suffix=".phps" method="create" />
    </filter>
</representations>'
            ],

            'representations.class.missing-method' => [
                'exception' => [
                    'regex' => '/missing a `method`/'
                ],
                'xml' => '
<representations>
    <filter>
        <class name="\Mill\Tests\Stubs\Representations\RepresentationStub" method="" />
    </filter>
</representations>'
            ],

            'representations.class.uncallable' => [
                'exception' => [
                    'exception' => UncallableRepresentationException::class
                ],
                'xml' => '
<representations>
    <filter>
        <class name="\UncallableClass" method="main" />
    </filter>
</representations>'
            ],

            'representations.directory.invalid' => [
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/does not exist/'
                ],
                'xml' => '
<representations>
    <filter>
        <directory name="invalid/directory" method="main" />
    </filter>
</representations>'
            ],

            'representations.error.uncallable' => [
                'exception' => [
                    'exception' => UncallableErrorRepresentationException::class
                ],
                'xml' => '
<representations>
    <filter>
        <class name="\Mill\Examples\Showtimes\Representations\Movie" method="create" />
    </filter>

    <errors>
        <class name="\Uncallable" method="create" needsErrorCode="false" />
    </errors>
</representations>'
            ],

            'representations.error.missing-method' => [
                'exception' => [
                    'regex' => '/missing a `method`/'
                ],
                'xml' => '
<representations>
    <filter>
        <class name="\Mill\Examples\Showtimes\Representations\Movie" method="create" />
    </filter>

    <errors>
        <class name="\Mill\Examples\Showtimes\Representations\Error" method="" needsErrorCode="false" />
    </errors>
</representations>'
            ],

            /**
             * <parameterTokens>
             *
             */
            'parametertokens.invalid' => [
                'exception' => [
                    'regex' => '/invalid parameter token/'
                ],
                'xml' => '
<parameterTokens>
    <token name=""></token>
</parameterTokens>'
            ],

            /**
             * <pathParams>
             *
             */
            'pathparams.invalid' => [
                'exception' => [
                    'regex' => '/invalid translation text/'
                ],
                'xml' => '
<pathParams>
    <translations>
        <translation from="" to="" />
    </translations>
</pathParams>'
            ]
        ];
    }
}
