<?php
namespace Mill\Tests\Parser;

use Mill\Parser\HTTPResponseMSON;
use Mill\Tests\TestCase;

class HTTPResponseMSONTest extends TestCase
{
    /**
     * @dataProvider providerTestParse
     * @param string $content
     * @param array $expected
     */
    public function testParse(string $content, array $expected): void
    {
        $mson = (new HTTPResponseMSON(__CLASS__, __METHOD__))->parse($content);
        $this->assertSame($expected, $mson->toArray());
    }

    public function providerTestParse(): array
    {
        return [
            'return-only' => [
                'content' => '(deleted)',
                'expected' => [
                    'capability' => false,
                    'description' => null,
                    'error_code' => false,
                    'http_code' => 'deleted',
                    'representation' => false
                ]
            ],
            'return-with-description' => [
                'content' => '(notmodified) - If no data has been changed.',
                'expected' => [
                    'capability' => false,
                    'description' => 'If no data has been changed.',
                    'error_code' => false,
                    'http_code' => 'notmodified',
                    'representation' => false
                ]
            ],
            'return-with-long-description' => [
                'content' => '(notmodified) - Voluptate culpa ex, eiusmod rump sint id. Venison non ribeye ' .
                    'landjaeger laboris, enim jowl culpa meatloaf dolore mollit anim. Bacon shankle eiusmod ' .
                    'hamburger enim. Laboris lorem pastrami t-bone tempor ullamco swine commodo tri-tip in sirloin.',
                'expected' => [
                    'capability' => false,
                    'description' => 'Voluptate culpa ex, eiusmod rump sint id. Venison non ribeye landjaeger ' .
                        'laboris, enim jowl culpa meatloaf dolore mollit anim. Bacon shankle eiusmod hamburger enim. ' .
                        'Laboris lorem pastrami t-bone tempor ullamco swine commodo tri-tip in sirloin.',
                    'error_code' => false,
                    'http_code' => 'notmodified',
                    'representation' => false
                ]
            ],
            'return-with-representation' => [
                'content' => '(collection, Movie)',
                'expected' => [
                    'capability' => false,
                    'description' => null,
                    'error_code' => false,
                    'http_code' => 'collection',
                    'representation' => 'Movie'
                ]
            ],
            'return-with-representation-and-description' => [
                'content' => '(collection, Movie) - A collection of movies.',
                'expected' => [
                    'capability' => false,
                    'description' => 'A collection of movies.',
                    'error_code' => false,
                    'http_code' => 'collection',
                    'representation' => 'Movie'
                ]
            ],
            'thrown-only' => [
                'content' => '(404, Error) - If the movie could not be found.',
                'expected' => [
                    'capability' => false,
                    'description' => 'If the movie could not be found.',
                    'error_code' => false,
                    'http_code' => '404',
                    'representation' => 'Error'
                ]
            ],
            'thrown-with-description-type' => [
                'content' => '(404, Error) - {movie}',
                'expected' => [
                    'capability' => false,
                    'description' => '{movie}',
                    'error_code' => false,
                    'http_code' => '404',
                    'representation' => 'Error'
                ]
            ],
            'thrown-with-description-type-and-subtype' => [
                'content' => '(404, Error) - {movie,theater}',
                'expected' => [
                    'capability' => false,
                    'description' => '{movie,theater}',
                    'error_code' => false,
                    'http_code' => '404',
                    'representation' => 'Error'
                ]
            ],
            'thrown-with-description-that-has-parenthesis' => [
                'content' => '(403, Error) - This is a description with a (parenthesis of something).',
                'expected' => [
                    'capability' => false,
                    'description' => 'This is a description with a (parenthesis of something).',
                    'error_code' => false,
                    'http_code' => '403',
                    'representation' => 'Error'
                ]
            ],
            'thrown-with-capability' => [
                'content' => '(404, Error, BUY_TICKETS) - If the movie could not be found.',
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => 'If the movie could not be found.',
                    'error_code' => false,
                    'http_code' => '404',
                    'representation' => 'Error'
                ]
            ],
            'thrown-with-error-code' => [
                'content' => '(403, Coded error, 666) - If the user is not allowed to edit that movie.',
                'expected' => [
                    'capability' => false,
                    'description' => 'If the user is not allowed to edit that movie.',
                    'error_code' => '666',
                    'http_code' => '403',
                    'representation' => 'Coded error'
                ]
            ],
            'thrown-with-error-code-and-capability' => [
                'content' => '(404, Coded error, 666, BUY_TICKETS) - {movie,theater}',
                'expected' => [
                    'capability' => 'BUY_TICKETS',
                    'description' => '{movie,theater}',
                    'error_code' => '666',
                    'http_code' => '404',
                    'representation' => 'Coded error'
                ]
            ]
        ];
    }
}
