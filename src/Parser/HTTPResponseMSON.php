<?php
namespace Mill\Parser;

class HTTPResponseMSON extends MSON
{
    /**
     * This is the regex to match a Mill-flavored MSON HTTP return string.
     *
     * Examples:
     *
     *  - (deleted)
     *  - (notmodified) If no data has been changed.
     *  - (collection, Movie
     *  - (collection, Movie) - A collection of movies.
     *  - (404, Error) - If the movie could not be found.
     *  - (404, Error) {movie}
     *  - (404, Error) {movie,theater}
     *  - (403, Error) This is a description with a (parenthesis of something).
     *  - (404, Error, BUY_TICKETS) If the movie could not be found.
     *  - (403, Coded error, 666) If the user is not allowed to edit that movie.
     *  - (404, Coded error, 666, BUY_TICKETS) {movie,theater}
     */
    const REGEX_MSON = '/' .
        '\(' .
            '(?P<httpCode>[\w]+)' .
            '(, (?P<representation>[\w\s]+))?' .
            '(, (?P<errorCode>[\w]+))?' .
            '(, (?P<capability>[\w]+))?' .
        '\)((\n|\s)*?-?(\n|\s)*?(?P<description>.+))?' .
        '/uis';

    /**
     * Optional unique error code for the error that this exception handles.
     *
     * @var false|string
     */
    protected $error_code = false;

    /**
     * HTTP code that this response throws.
     *
     * @var false|string
     */
    protected $http_code = false;

    /**
     * Name of the representation that this annotation responds with. Can be either a parsed representation name, or
     * `string`.
     *
     * @var false|string
     */
    protected $representation = false;

    /**
     * Given a piece of Mill-flavored MSON content, parse it out.
     *
     * @param string $content
     * @return self
     */
    public function parse(string $content)
    {
        preg_match(self::REGEX_MSON, $content, $matches);

        foreach (['httpCode', 'representation', 'errorCode', 'capability', 'description'] as $name) {
            if (!empty($matches[$name])) {
                if ($name === 'httpCode') {
                    $this->http_code = $matches[$name];
                } elseif ($name === 'errorCode') {
                    $this->error_code = $matches[$name];
                } else {
                    $this->{$name} = $matches[$name];
                }
            }
        }

        // Is the error code actually a capability?
        if ($this->config->hasCapability($this->error_code)) {
            $this->capability = $this->error_code;
            $this->error_code = false;
        }

        if (!empty($this->description)) {
            $this->description = ltrim($this->description, '- ');
        }

        return $this;
    }

    /**
     * @return false|string
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * @return false|string
     */
    public function getHttpCode()
    {
        return $this->http_code;
    }

    /**
     * @return false|string
     */
    public function getRepresentation()
    {
        return $this->representation;
    }

    /**
     * Get parsed MSON content in an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'capability' => $this->getCapability(),
            'description' => $this->getDescription(),
            'error_code' => $this->getErrorCode(),
            'http_code' => $this->getHttpCode(),
            'representation' => $this->getRepresentation()
        ];
    }
}
