<?php
namespace Mill\Tests;

use Mill\Config;

class ConfigTest extends TestCase
{
    public function testLoadFromXML()
    {
        $config = $this->getConfig();

        $this->assertSame('1.0', $config->getSinceApiVersion());
        $this->assertSame('1.1', $config->getDefaultApiVersion());
        $this->assertSame('1.2', $config->getLatestApiVersion());

        $this->assertSame([
            'BUY_TICKETS',
            'MOVIE_RATINGS',
            'NONE'
        ], $config->getCapabilities());

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

        $this->assertSame([
            '\Mill\Examples\Showtimes\Representations\Representation' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Representation',
                'no_method' => true
            ],
            '\Mill\Examples\Showtimes\Representations\Movie' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Movie',
                'method' => 'create'
            ],
            '\Mill\Examples\Showtimes\Representations\Theater' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Theater',
                'method' => 'create'
            ]
        ], $config->getRepresentations());

        $this->assertSame([
            '\Mill\Examples\Showtimes\Representations\Error' => [
                'class' => '\Mill\Examples\Showtimes\Representations\Error',
                'needs_error_code' => false
            ],
            '\Mill\Examples\Showtimes\Representations\CodedError' => [
                'class' => '\Mill\Examples\Showtimes\Representations\CodedError',
                'needs_error_code' => true
            ]
        ], $config->getErrorRepresentations());

        $this->assertSame([
            'string'
        ], $config->getIgnoredRepresentations());

        $this->assertEmpty($config->getUriSegmentTranslations());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /does not exist/
     */
    public function testLoadFromXMLFailsIfConfigFileDoesNotExist()
    {
        $filesystem = $this->getContainer()->getFilesystem();
        Config::loadFromXML($filesystem, 'non-existent.xml');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /is invalid/
     */
    public function testLoadFromXMLFailsIfConfigFileIsInvalid()
    {
        $filesystem = $this->getContainer()->getFilesystem();
        $filesystem->write('empty.xml', '');

        Config::loadFromXML($filesystem, 'empty.xml');
    }

    /**
     * @expectedException \Mill\Exceptions\Config\ValidationException
     */
    public function testXSDValidation()
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
     * @dataProvider badXMLFilesProvider
     */
    public function testLoadFromXMLFailuresOnVariousBadXMLFiles($includes, $exception_details, $xml)
    {
        if (isset($exception_details['exception'])) {
            $this->expectException($exception_details['exception']);
        } else {
            $this->expectException('\InvalidArgumentException');
            $this->expectExceptionMessageRegExp($exception_details['regex']);
        }

        // Customize the provider XML so we don't need to have a bunch of DRY'd XML everywhere.
        $controllers = $representations = false;
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
<mill sinceApiVersion="3.2" bootstrap="vendor/autoload.php">
    $controllers
    $representations
    $xml
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
     * @expectedException \Mill\Exceptions\Config\UnconfiguredRepresentationException
     */
    public function testDoesRepresentationExistFailsIfRepresentationIsNotConfigured()
    {
        $this->getConfig()->doesRepresentationExist('UnconfiguredClass');
    }

    /**
     * @return array
     */
    public function badXMLFilesProvider()
    {
        return [
            /**
             * <controllers>
             *
             */
            'controllers.directory.invalid' => [
                'includes' => ['representations'],
                'exception' => [
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
                'includes' => ['representations'],
                'exception' => [
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
                'includes' => ['representations'],
                'exception' => [
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
                'includes' => ['controllers'],
                'exception' => [
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

            'representations.exclude.uncallable' => [
                'includes' => ['controllers'],
                'exception' => [
                    'exception' => '\Mill\Exceptions\Config\UncallableRepresentationException'
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <class name="\Mill\Examples\Showtimes\Representations\Movie" method="create" />
        <exclude>
            <class name="\UncallableClass" />
        </exclude>
    </filter>
</representations>
XML
            ],

            'representations.class.missing-method' => [
                'includes' => ['controllers'],
                'exception' => [
                    'regex' => '/missing a `method`/'
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <class name="\Mill\Tests\Stubs\Representations\RepresentationStub" />
    </filter>
</representations>
XML
            ],

            'representations.class.uncallable' => [
                'includes' => ['controllers'],
                'exception' => [
                    'exception' => '\Mill\Exceptions\Config\UncallableRepresentationException'
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
                'includes' => ['controllers'],
                'exception' => [
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
                'includes' => ['controllers'],
                'exception' => [
                    'exception' => '\Mill\Exceptions\Config\UncallableErrorRepresentationException'
                ],
                'xml' => <<<XML
<representations>
    <filter>
        <class name="\Mill\Examples\Showtimes\Representations\Movie" method="create" />
    </filter>

    <errors>
        <class name="\Uncallable" needsErrorCode="false" />
    </errors>
</representations>
XML
            ],

            /**
             * <uriSegments>
             *
             */
            'urisegments.invalid' => [
                'includes' => ['controllers', 'representations'],
                'exception' => [
                    'regex' => '/invalid translation text/'
                ],
                'xml' => <<<XML
<uriSegments>
    <translations>
        <translation from="" to="" />
    </translations>
</uriSegments>
XML
            ]
        ];
    }
}
