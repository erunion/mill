<?php
namespace Mill\Parser;

use Mill\Exceptions\Resource\Annotations\UnsupportedTypeException;

class MSON
{
    /**
     * This is the regex to match a Mill-flavor MSON string.
     *
     * Examples:
     *
     *  - content_rating (string) - MPAA rating
     *  - content_rating `G` (string, required) - MPAA rating
     *  - content_rating `G` (string, optional, MOVIE_RATINGS) - MPAA rating
     *  - content_rating `G` (string, MOVIE_RATINGS) - MPAA rating
     *
     * @var string
     */
    const REGEX_MSON = '/((?P<field>\w+) (`(?P<sample_data>.+)` )?' .
        '\((?P<type>\w+)(, (?P<required>required|optional))?(, (?P<capability>\w+))?\) - (?P<description>.+))/uis';

    /**
     * This is the regex to match Mill-flavor MSON enum members.
     *
     * Examples:
     *
     *  - content_rating `G` (string, optional, MOVIE_RATINGS) - This denotes the
     *      [MPAA rating](http://www.mpaa.org/film-ratings/) for the movie.
     *      + Members
     *          - `G` - G rated
     *          - `PG` - PG rated
     *          - `PG-13` - PG-13 rated
     *
     *  - content_rating `G` (string, optional, MOVIE_RATINGS) - MPAA rating
     *      + Members
     *          - `G`
     *          - `PG`
     *          - `PG-13`
     *
     * @var string
     */
    const REGEX_MSON_ENUM = '/(?:\+ Members\n(?:\s*?))?(?:- `(?P<value>.*?)`( - (?P<description>.*?))?)(?:$|\n)/ui';

    /**
     * Take a multi-line string/pagraph, remove any multi-lines and contract sentences.
     *
     * Examples:
     *
     *  - "If there is a problem with the
     *      request." becomes "If there is a problem with the request."
     *
     * @todo This does not currently support multi-paragraph strings as those seem a bit of overkill for their usages,
     *  but it'd be nice to support at some point regardless.
     */
    const REGEX_CLEAN_MULTILINE = '/(\s)?[ \t]*(\r\n|\n)[ \t]*(\s)/';

    /**
     * Name of the controller that this MSON is being parsed from.
     *
     * @var string
     */
    protected $controller;

    /**
     * Name of the controller method that MSON is being parsed from.
     *
     * @var mixed
     */
    protected $method;

    /**
     * Name of the field that was parsed out of the MSON content.
     *
     * @var string|false
     */
    protected $field;

    /**
     * Sample data that was parsed out of the MSON content.
     *
     * @var string|false
     */
    protected $sample_data;

    /**
     * Type of field that this MSON content represents.
     *
     * @var string|false
     */
    protected $type;

    /**
     * Is this MSON content designated as being required?
     *
     * @var bool
     */
    protected $is_required = false;

    /**
     * Application-specific capability that was parsed out of the MSON content.
     *
     * @var string|false
     */
    protected $capability;

    /**
     * Parsed description from the MSON content.
     *
     * @var string|false
     */
    protected $description;

    /**
     * Array of enumerated values from the MSON content.
     *
     * @var array<string, string>
     */
    protected $values = [];

    /**
     * Supported MSON field types.
     *
     * @var array
     */
    protected $supported_types = [
        'array',
        'boolean',
        'datetime',
        'float',
        'enum',
        'integer',
        'number',
        'object',
        'string',
        'timestamp'
    ];

    /**
     * @param string $controller
     * @param string$method
     */
    public function __construct($controller, $method)
    {
        $this->controller = $controller;
        $this->method = $method;
    }

    /**
     * Given a piece of Mill-flavored MSON content, parse it out.
     *
     * @param string $content
     * @return MSON
     * @throws UnsupportedTypeException If an unsupported MSON field type has been supplied.
     */
    public function parse($content)
    {
        preg_match(self::REGEX_MSON, $content, $matches);

        $this->field = (isset($matches['field'])) ? $matches['field'] : false;
        $this->sample_data = (isset($matches['sample_data'])) ? $matches['sample_data'] : false;
        $this->type = (isset($matches['type'])) ? $matches['type'] : false;
        $this->capability = (isset($matches['capability'])) ? $matches['capability'] : false;
        $this->description = (isset($matches['description'])) ? $matches['description'] : false;

        if (isset($matches['required'])) {
            if (!empty($matches['required']) && strtolower($matches['required']) == 'required') {
                $this->is_required = true;
            }
        }

        // Verify that the supplied type is supported.
        if (!empty($this->type)) {
            if (!in_array(strtolower($this->type), $this->supported_types)) {
                throw UnsupportedTypeException::create($content, $this->controller, $this->method);
            }
        }

        // Parse out enum values if present, and remove them from the parsed description afterwards.
        if (!empty($this->description)) {
            preg_match_all(self::REGEX_MSON_ENUM, $this->description, $matches);
            if (!empty($matches['value']) && !empty($matches['description'])) {
                $this->values = $this->parseValues($matches['value'], $matches['description']);

                // Remove any parsed enum values from the description.
                $this->description = preg_replace(self::REGEX_MSON_ENUM, '', $this->description);
                $this->description = trim($this->description);
            }

            // The description might be on multiple lines, so let's clean it up a bit.
            // @todo Multi-paragraph descriptions seems like a bit of overkill, but it'd be nice to add support.
            $this->description = preg_replace(self::REGEX_CLEAN_MULTILINE, ' ', $this->description);
        }

        return $this;
    }

    /**
     * Given an array of values and descriptions.
     *
     * @param array $values
     * @param array $descriptions
     * @return array
     */
    protected function parseValues($values, $descriptions)
    {
        $enum = [];
        foreach ($values as $k => $value) {
            $value = trim($value);
            $description = trim($descriptions[$k]);

            $enum[$value] = $description;
        }

        // Keep the array of values alphabetical so it's cleaner when generated into documentation.
        ksort($enum);

        return $enum;
    }

    /**
     * Name of the field that was parsed out of the MSON content.
     *
     * @return false|string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sample data that was parsed out of the MSON content.
     *
     * @return false|string
     */
    public function getSampleData()
    {
        return $this->sample_data;
    }

    /**
     * Type of field that this MSON content represents.
     *
     * @return false|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Is this MSON content designated as being required?
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->is_required;
    }

    /**
     * Application-specific capability that was parsed out of the MSON content.
     *
     * @return false|string
     */
    public function getCapability()
    {
        return $this->capability;
    }

    /**
     * Parsed description from the MSON content.
     *
     * @return false|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Array of enumerated values from the MSON content.
     *
     * @return array<string, string>
     */
    public function getValues()
    {
        return $this->values;
    }
}
