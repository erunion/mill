<?php
namespace Mill\Parser;

use Mill\Container;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Config\UnconfiguredRepresentationException;
use Mill\Exceptions\MSON\MissingOptionsException;

class MSON
{
    /**
     * This is the regex to match Mill-flavored MSON enum members.
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
     * @todo Add a test for a member description that exists on multiple lines.
     */
    const REGEX_MSON_ENUM = '/(?:\+ Members\n(?:\s*?))?(?:- `(?P<value>.*?)`( - (?P<description>.*?))?)(?:$|\n)/ui';

    /**
     * Take a multi-line string or paragraph, remove any multi-lines, and contract sentences.
     *
     * Examples:
     *
     *  - "If there is a problem with the
     *      request." becomes "If there is a problem with the request."
     *
     * @todo This does not currently support multi-paragraph strings.
     */
    const REGEX_CLEAN_MULTILINE = '/(\s)?[ \t]*(\r\n|\n)[ \t]*(\s)/';

    /**
     * Controller that this MSON is being parsed from.
     *
     * @var string
     */
    protected $class;

    /**
     * Controller method that MSON is being parsed from.
     *
     * @var mixed
     */
    protected $method;

    /**
     * Name of the field that was parsed out of the MSON content.
     *
     * @var string
     */
    protected $field;

    /**
     * Sample data that was parsed out of the MSON content.
     *
     * @var string|false
     */
    protected $sample_data = false;

    /**
     * Type of field that this MSON content represents.
     *
     * @var string
     */
    protected $type;

    /**
     * Subtype of the type of field that this MSON content represents.
     *
     * @var string|false
     */
    protected $subtype = false;

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
    protected $capability = false;

    /**
     * Parsed description from the MSON content.
     *
     * @var string
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
        'date',
        'datetime',
        'float',
        'enum',
        'integer',
        'number',
        'object',
        'string',
        'timestamp',
        'uri'
    ];

    /**
     * @param string $class
     * @param string $method
     */
    public function __construct($class, $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Given a piece of Mill-flavored MSON content, parse it out.
     *
     * @param string $content
     * @return MSON
     * @throws UnsupportedTypeException If an unsupported MSON field type has been supplied.
     * @throws MissingOptionsException If a supplied MSON type of `enum` missing corresponding acceptable values.
     */
    public function parse($content)
    {
        /**
         * This is the regex to match a Mill-flavored MSON string.
         *
         * Examples:
         *
         *  - content_rating (string) - MPAA rating
         *  - content_rating `G` (string, required) - MPAA rating
         *  - content_rating `G` (string, optional, MOVIE_RATINGS) - MPAA rating
         *  - content_rating `G` (string, MOVIE_RATINGS) - MPAA rating
         *  - websites.description (string) - The websites' description
         *  - websites (array<object>) - The users' list of websites.
         *  - cast (array<\Mill\Examples\Showtimes\Representations\Person>) - Cast
         *  - director (\Mill\Examples\Showtimes\Representations\Person) - Director
         *
         * @var string
         */
        $regex_mson = '/((?P<field>[\w.\*]+) (`(?P<sample_data>.+)` )?' .
            '\((?P<type>[\w\\\]+)(<(?P<subtype>[\w\\\]+)>)?(, (?P<required>required|optional))?(, ' .
            '(?P<capability>\w+))?\)(\n|\s)+-(\n|\s)+(?P<description>.+))/uis';

        preg_match($regex_mson, $content, $matches);

        foreach (['field', 'type', 'description', 'sample_data', 'subtype', 'capability'] as $name) {
            if (isset($matches[$name]) && !empty($matches[$name])) {
                $this->{$name} = $matches[$name];
            }
        }

        if (isset($matches['required'])) {
            if (!empty($matches['required']) && strtolower($matches['required']) == 'required') {
                $this->is_required = true;
            }
        }

        // Verify that the supplied type, and any subtype if present, is supported.
        if (!empty($this->type)) {
            $config = Container::getConfig();

            if (!in_array(strtolower($this->type), $this->supported_types)) {
                try {
                    // If this isn't a valid representation, then it's an invalid type.
                    $config->doesRepresentationExist($this->type);
                } catch (UnconfiguredRepresentationException $e) {
                    throw UnsupportedTypeException::create($content, $this->class, $this->method);
                }
            }

            if (!empty($this->subtype)) {
                switch ($this->type) {
                    case 'array':
                        if (!in_array(strtolower($this->subtype), $this->supported_types)) {
                            try {
                                // If this isn't a valid representation, then it's an invalid type.
                                $config->doesRepresentationExist($this->subtype);
                            } catch (UnconfiguredRepresentationException $e) {
                                throw UnsupportedTypeException::create($content, $this->class, $this->method);
                            }
                        }
                        break;

                    default:
                        throw UnsupportedTypeException::create($content, $this->class, $this->method);
                }
            }
        }

        // Parse out enum values if present, and remove them from the parsed description afterwards.
        if (!empty($this->description)) {
            preg_match_all(self::REGEX_MSON_ENUM, $this->description, $enum_matches);
            if (!empty($enum_matches['value']) && !empty($enum_matches['description'])) {
                $this->values = $this->parseValues($enum_matches['value'], $enum_matches['description']);

                // Remove any parsed enum values from the description.
                $this->description = preg_replace(self::REGEX_MSON_ENUM, '', $this->description);
                $this->description = trim($this->description);
            }

            // The description might be on multiple lines, so let's clean it up a bit.
            // @todo Multi-paragraph descriptions seems like a bit of overkill, but it'd be nice to add support.
            $this->description = preg_replace(self::REGEX_CLEAN_MULTILINE, ' ', $this->description);
        }

        if ($this->type === 'enum' && empty($this->values)) {
            throw MissingOptionsException::create($this->type, $this->class, $this->method);
        }

        return $this;
    }

    /**
     * Given an array of values and descriptions.
     *
     * @param array $values
     * @param array $descriptions
     * @return array<string, string>
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Subtype of the type of field that this MSON content represents.
     *
     * @return false|string
     */
    public function getSubtype()
    {
        return $this->subtype;
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
     * @return string|false
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

    /**
     * Get parsed MSON content in an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'capability' => $this->getCapability(),
            'description' => $this->getDescription(),
            'field' => $this->getField(),
            'required' => $this->isRequired(),
            'sample_data' => $this->getSampleData(),
            'subtype' => $this->getSubtype(),
            'type' => $this->getType(),
            'values' => $this->getValues()
        ];
    }
}
