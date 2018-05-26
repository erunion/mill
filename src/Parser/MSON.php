<?php
namespace Mill\Parser;

use Mill\Container;
use Mill\Exceptions\Annotations\UnknownErrorRepresentationException;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Config\UnconfiguredErrorRepresentationException;
use Mill\Exceptions\Config\UnconfiguredRepresentationException;
use Mill\Exceptions\MSON\ImproperlyWrittenEnumException;
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
     * @var string
     */
    protected $method;

    /**
     * Name of the field that was parsed out of the MSON content.
     *
     * @var null|string
     */
    protected $field = null;

    /**
     * Sample data that was parsed out of the MSON content.
     *
     * @var false|string
     */
    protected $sample_data = false;

    /**
     * Type of field that this MSON content represents.
     *
     * @var null|string
     */
    protected $type = null;

    /**
     * Subtype of the type of field that this MSON content represents.
     *
     * @var false|string
     */
    protected $subtype = false;

    /**
     * Is this MSON content designated as being required?
     *
     * @var bool
     */
    protected $is_required = false;

    /**
     * Is this MSON content designated as nullable?
     *
     * @var bool
     */
    protected $is_nullable = false;

    /**
     * Application-specific vendor tags that were parsed out of the MSON content.
     *
     * @var array
     */
    protected $vendor_tags = [];

    /**
     * Parsed description from the MSON content.
     *
     * @var null|string
     */
    protected $description = null;

    /**
     * Array of enumerated values from the MSON content.
     *
     * @var array<string, string>
     */
    protected $values = [];

    /**
     * Allow all kind of subtypes. Used for `@api-error` annotations to allow error codes.
     *
     * @var bool
     */
    protected $allow_all_subtypes = false;

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
    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Given a piece of Mill-flavored MSON content, parse it out.
     *
     * @param string $content
     * @return self
     * @throws UnsupportedTypeException If an unsupported MSON field type has been supplied.
     * @throws MissingOptionsException If a supplied MSON type of `enum` missing corresponding acceptable values.
     * @throws UnsupportedTypeException If a supplied MSON type of `string` should actually be written as `enum`.
     */
    public function parse(string $content): self
    {
        /**
         * This is the regex to match a Mill-flavored MSON string.
         *
         * Examples:
         *
         *  - content_rating (string) - MPAA rating
         *  - content_rating `G` (string, required) - MPAA rating
         *  - content_rating `G` (string, required, nullable) - MPAA rating
         *  - content_rating `G` (string, optional, tag:MOVIE_RATINGS) - MPAA rating
         *  - content_rating `G` (string, optional, nullable, tag:MOVIE_RATINGS) - MPAA rating
         *  - content_rating `G` (string, tag:MOVIE_RATINGS) - MPAA rating
         *  - content_rating `G` (string, tag:MOVIE_RATINGS, needs:validUser) - MPAA rating
         *  - websites.description (string) - The websites' description
         *  - websites (array<object>) - The users' list of websites.
         *  - cast (array<\Mill\Examples\Showtimes\Representations\Person>) - Cast
         *  - director (\Mill\Examples\Showtimes\Representations\Person) - Director
         *
         * @var string
         */
        $regex_mson = '/((?P<field>[\w.\*]+) (`(?P<sample_data>.+)` )?' .
            '\((?P<type>[\w\\\]+)(<(?P<subtype>[\w\\\]+)>)?(, (?P<required>required|optional))?(, ' .
            '(?P<nullable>nullable))?(?P<vendor_tag>(, ([\w]+:[\w]+))*?)\)(\n|\s)+-(\n|\s)+(?P<description>.+))/uis';

        preg_match($regex_mson, $content, $matches);

        foreach (['field', 'type', 'description', 'sample_data', 'subtype'] as $name) {
            if (isset($matches[$name])) {
                // Sample data can be input as "0", so we need some special casing to account for that.
                if (!empty($matches[$name]) || $name === 'sample_data') {
                    $this->{$name} = $matches[$name];
                }
            }
        }

        if (isset($matches['vendor_tag'])) {
            $vendor_tags = explode(',', $matches['vendor_tag']);
            $vendor_tags = array_filter($vendor_tags);
            $vendor_tags = array_values($vendor_tags);
            $vendor_tags = array_map(
                function (string $tag): string {
                    return trim($tag);
                },
                $vendor_tags
            );

            $this->vendor_tags = $vendor_tags;
        }

        if (isset($matches['required'])) {
            if (!empty($matches['required']) && strtolower($matches['required']) == 'required') {
                $this->is_required = true;
            }
        }

        if (isset($matches['nullable'])) {
            if (!empty($matches['nullable']) && strtolower($matches['nullable']) == 'nullable') {
                $this->is_nullable = true;
            }
        }

        // Verify that the supplied type, and any subtype if present, is supported.
        if (!empty($this->type)) {
            $config = Container::getConfig();

            if (!in_array(strtolower($this->type), $this->supported_types)) {
                try {
                    // If we're allowing all subtypes, then we're dealing with error states and the `@api-error`
                    // annotation, so we should look at error representations instead here.
                    if ($this->allow_all_subtypes) {
                        $config->doesErrorRepresentationExist($this->type);
                    } else {
                        // If this isn't a valid representation, then it's an invalid type.
                        $config->doesRepresentationExist($this->type);
                    }
                } catch (UnconfiguredRepresentationException $e) {
                    throw UnsupportedTypeException::create($content, $this->class, $this->method);
                } catch (UnconfiguredErrorRepresentationException $e) {
                    throw UnknownErrorRepresentationException::create($content, $this->class, $this->method);
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
                        if ($this->allow_all_subtypes) {
                            break;
                        }

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

        if ($this->type !== 'enum' && !empty($this->values)) {
            throw ImproperlyWrittenEnumException::create($content, $this->class, $this->method);
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
    protected function parseValues(array $values, array $descriptions): array
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
     * @return null|string
     */
    public function getField(): ?string
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
     * @return null|string
     */
    public function getType(): ?string
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
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Is this MSON content designated as nullable?
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->is_nullable;
    }

    /**
     * Application-specific vendor tags that were parsed from the MSON content.
     *
     * @return array
     */
    public function getVendorTags(): array
    {
        return $this->vendor_tags;
    }

    /**
     * Parsed description from the MSON content.
     *
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Array of enumerated values from the MSON content.
     *
     * @return array<string, string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Allow all kind of subtypes. Used for `@api-error` annotations to allow error codes.
     *
     * @return MSON
     */
    public function allowAllSubtypes(): self
    {
        $this->allow_all_subtypes = true;
        return $this;
    }

    /**
     * Get parsed MSON content in an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'description' => $this->getDescription(),
            'field' => $this->getField(),
            'nullable' => $this->isNullable(),
            'required' => $this->isRequired(),
            'sample_data' => $this->getSampleData(),
            'subtype' => $this->getSubtype(),
            'type' => $this->getType(),
            'values' => $this->getValues(),
            'vendor_tags' => $this->getVendorTags(),
        ];
    }
}
