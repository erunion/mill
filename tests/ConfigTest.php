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
            'create',
            'delete',
            'edit',
            'public'
        ], $config->getScopes());

        $this->assertSame([
            '\Mill\Examples\Showtimes\Controllers\Movie',
            '\Mill\Examples\Showtimes\Controllers\Movies',
            '\Mill\Examples\Showtimes\Controllers\Theater',
            '\Mill\Examples\Showtimes\Controllers\Theaters'
        ], $config->getControllers());

        $representations = [
            'standard' => [
                '\Mill\Examples\Showtimes\Representations\Movie' => [
                    'class' => '\Mill\Examples\Showtimes\Representations\Movie',
                    'method' => 'create'
                ],
                '\Mill\Examples\Showtimes\Representations\Person' => [
                    'class' => '\Mill\Examples\Showtimes\Representations\Person',
                    'method' => 'create'
                ],
                '\Mill\Examples\Showtimes\Representations\Theater' => [
                    'class' => '\Mill\Examples\Showtimes\Representations\Theater',
                    'method' => 'create'
                ]
            ],
            'error' => [
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
            ]
        ];

        $this->assertSame($representations['standard'], $config->getRepresentations());
        $this->assertSame($representations['error'], $config->getErrorRepresentations());
        $this->assertSame(
            array_merge($representations['standard'], $representations['error']),
            $config->getAllRepresentations()
        );

        $this->assertSame([
            '\Mill\Examples\Showtimes\Representations\Error',
            '\Mill\Examples\Showtimes\Representations\CodedError',
            '\Mill\Examples\Showtimes\Representations\Representation'
        ], $config->getExcludedRepresentations());

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
     * @param array $includes
     * @param array $exception_details
     * @param string $xml
     * @throws \Exception
     */
    public function testLoadFromXMLFailuresOnVariousBadXMLFiles(
        array $includes,
        array $exception_details,
        string $xml
    ): void {
        if (isset($exception_details['exception'])) {
            $this->expectException($exception_details['exception']);
        } else {
            $this->expectException(\DomainException::class);
            $this->expectExceptionMessageRegExp($exception_details['regex']);
        }

        // Customize the provider XML so we don't need to have a bunch of DRY'd XML everywhere.
        $versions = $controllers = $representations = false;
        if (in_array('versions', $includes)) {
            $versions = <<<XML
<versions>
    <version name="1.0" releaseDate="2017-01-01" />
    <version name="1.1" releaseDate="2017-02-01" default="true" />
</versions>
XML;
        }

        if (in_array('controllers', $includes)) {
            $controllers = <<<XML
<controllers>
    <filter>
        <class name="\Mill\Examples\Showtimes\Controllers\Movie" />
    </filter>
</controllers>
XML;
        }

        if (in_array('representations', $includes)) {
            $representations = <<<XML
<representations>
    <filter>
        <class name="\Mill\Examples\Showtimes\Representations\Movie" method="create" />
    </filter>
</representations>
XML;
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mill bootstrap="vendor/autoload.php">
    $versions
    $controllers
    $representations
    $xml
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
                'includes' => ['controllers', 'representations'],
                'exception' => [
                    'regex' => '/You must set/'
                ],
                'xml' => <<<XML
<versions>
    <version name="1.0" releaseDate="2017-01-01" />
</versions>
XML
            ],

            'versions.multiple-defaults' => [
                'includes' => ['controllers', 'representations'],
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/Multiple default API versions/'
                ],
                'xml' => <<<XML
<versions>
    <version name="1.0" releaseDate="2017-01-01" default="true" />
    <version name="1.1" releaseDate="2017-02-01" default="true" />
</versions>
XML
            ],

            /**
             * <compilers>
             *
             */
            'compilers.exclude.invalid' => [
                'includes' => ['versions', 'controllers', 'representations'],
                'exception' => [
                    'regex' => '/invalid compiler group exclusion/'
                ],
                'xml' => <<<XML
<compilers>
    <excludes>
        <exclude group="" />
    </excludes>
</compilers>
XML
            ],

            /**
             * <controllers>
             *
             */
            'controllers.directory.invalid' => [
                'includes' => ['versions', 'representations'],
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/does not exist/'
                ],
                'xml' => <<<XML
<controllers>
    <filter>
        <directory name="invalid/directory/" />
    </filter>
</controllers>
XML
            ],

            'controllers.none-found' => [
                'includes' => ['versions', 'representations'],
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/requires a set of controllers/'
                ],
                'xml' => <<<XML
<controllers>
    <filter>
        <directory name="resources/examples/Showtimes/Controllers/" suffix=".phps" />
    </filter>
</controllers>
XML
            ],

            'controllers.class.uncallable' => [
                'includes' => ['versions', 'representations'],
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/could not be called/'
                ],
                'xml' => <<<XML
<controllers>
    <filter>
        <class name="\UncallableClass" />
    </filter>
</controllers>
XML
            ],

            /**
             * <representations>
             *
             */
            'representations.none-found' => [
                'includes' => ['versions', 'controllers'],
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/requires a set of representations/'
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <directory name="resources/examples/Showtimes/Representations" suffix=".phps" method="create" />
    </filter>
</representations>
XML
            ],

            'representations.class.missing-method' => [
                'includes' => ['versions', 'controllers'],
                'exception' => [
                    'regex' => '/missing a `method`/'
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <class name="\Mill\Tests\Stubs\Representations\RepresentationStub" method="" />
    </filter>
</representations>
XML
            ],

            'representations.class.uncallable' => [
                'includes' => ['versions', 'controllers'],
                'exception' => [
                    'exception' => UncallableRepresentationException::class
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <class name="\UncallableClass" method="main" />
    </filter>
</representations>
XML
            ],

            'representations.directory.invalid' => [
                'includes' => ['versions', 'controllers'],
                'exception' => [
                    'exception' => \InvalidArgumentException::class,
                    'regex' => '/does not exist/'
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <directory name="invalid/directory" method="main" />
    </filter>
</representations>
XML
            ],

            'representations.error.uncallable' => [
                'includes' => ['versions', 'controllers'],
                'exception' => [
                    'exception' => UncallableErrorRepresentationException::class
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <class name="\Mill\Examples\Showtimes\Representations\Movie" method="create" />
    </filter>

    <errors>
        <class name="\Uncallable" method="create" needsErrorCode="false" />
    </errors>
</representations>
XML
            ],

            'representations.error.missing-method' => [
                'includes' => ['versions', 'controllers'],
                'exception' => [
                    'regex' => '/missing a `method`/'
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <class name="\Mill\Examples\Showtimes\Representations\Movie" method="create" />
    </filter>

    <errors>
        <class name="\Mill\Examples\Showtimes\Representations\Error" method="" needsErrorCode="false" />
    </errors>
</representations>
XML
            ],

            /**
             * <parameterTokens>
             *
             */
            'parametertokens.invalid' => [
                'includes' => ['versions', 'controllers', 'representations'],
                'exception' => [
                    'regex' => '/invalid parameter token/'
                ],
                'xml' => <<<XML
<parameterTokens>
    <token name=""></token>
</parameterTokens>
XML
            ],

            /**
             * <pathParams>
             *
             */
            'pathparams.invalid' => [
                'includes' => ['versions', 'controllers', 'representations'],
                'exception' => [
                    'regex' => '/invalid translation text/'
                ],
                'xml' => <<<XML
<pathParams>
    <translations>
        <translation from="" to="" />
    </translations>
</pathParams>
XML
            ]
        ];
    }
}
