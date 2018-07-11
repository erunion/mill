<?php
namespace Mill\Tests\Compiler\Specification\OpenApi;

use Mill\Compiler\Specification\OpenApi;
use Mill\Compiler\Specification\OpenApi\TagReducer;
use Mill\Parser\Version;
use Mill\Tests\TestCase;

class TagReducerTest extends TestCase
{
    /** @var array */
    protected $spec = [];

    public function setUp(): void
    {
        parent::setUp();

        $version = new Version('1.1.3', __CLASS__, __METHOD__);
        $compiler = new OpenApi($this->getConfig(), $version);
        $compiled = $compiler->compile();

        $this->spec = array_shift($compiled);
    }

    public function testReduceForTag(): void
    {
        $reducer = new TagReducer($this->spec);

        $reduced = $reducer->reduceForTag('Movies');
        $this->assertCount(1, $reduced['tags']);
        $this->assertSame([
            '/movie/{id}',
            '/movies',
            '/movies/{id}'
        ], array_keys($reduced['paths']));

        $reduced = $reducer->reduceForTag('Theaters');
        $this->assertCount(1, $reduced['tags']);
        $this->assertSame([
            '/theaters',
            '/theaters/{id}'
        ], array_keys($reduced['paths']));
    }

    public function testReduceForTagWithLoosePrefix(): void
    {
        $reducer = new TagReducer($this->spec);

        // `Movies\Core` isn't a tag that actually exits, but what we really want here is to pull back tags that match
        // `Movies`.
        $reduced = $reducer->reduceForTag('Movies\Core', true);
        $this->assertCount(1, $reduced['tags']);
        $this->assertSame([
            '/movie/{id}',
            '/movies',
            '/movies/{id}'
        ], array_keys($reduced['paths']));
    }
}
