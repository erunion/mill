<?php
namespace Mill\Parser;

use Mill\Config;
use Mill\Contracts\Arrayable;
use Mill\Exceptions\Annotations\UnknownErrorRepresentationException;
use Mill\Exceptions\Annotations\UnsupportedTypeException;
use Mill\Exceptions\Config\UnconfiguredErrorRepresentationException;
use Mill\Exceptions\Config\UnconfiguredRepresentationException;
use Mill\Exceptions\MSON\ImproperlyWrittenEnumException;
use Mill\Exceptions\MSON\MissingOptionsException;

class MSON implements Arrayable
{
    /**
     * This is the regex to match Mill-flavored MSON enum members.
     *
     * The regex here supports multi-line member value descriptions, but not multi-paragraph.
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
    const REGEX_MSON_ENUM = '/(?:\+ Members\n(?:\s*?))?(?:- `(?P<value>.*?)`( - ' .
        '(?P<description>(.*)((((\s+)([a-zA-Z\s.,`"_!?@~!%]+)(\n)*))+)?))?)/uim';

    /**
     * Take a multi-line string or paragraph, remove any multi-lines, and contract sentences.
     *
     * Examples:
     *
     *  - "If there is a problem with the
     *      request." becomes "If there is a problem with the request."
     *
     * @var string
     * @todo This does not currently support multi-paragraph strings.
     */
    const REGEX_CLEAN_MULTILINE = '/(\s)?[ \t]*(\r\n|\n)[ \t]*(\s)/';

    /** @var array Supported MSON field types. */
    const SUPPORTED_TYPES = [
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

    /** @var string Controller that this MSON is being parsed from. */
    protected $class;

    /** @var string Controller method that MSON is being parsed from. */
    protected $method;

    /** @var Config */
    protected $config;

    /** @var null|string Name of the field that was parsed out of the MSON content. */
    protected $field = null;

    /** @var false|string Sample data that was parsed out of the MSON content. */
    protected $sample_data = false;

    /** @var null|string Type of field that this MSON content represents. */
    protected $type = null;

    /** @var false|string Subtype of the type of field that this MSON content represents. */
    protected $subtype = false;

    /** @var bool Is this MSON content designated as being required? */
    protected $is_required = false;

    /** @var bool Is this MSON content designated as nullable? */
    protected $is_nullable = false;

    /** @var array Application-specific vendor tags that were parsed out of the MSON content. */
    protected $vendor_tags = [];

    /** @var null|string Parsed description from the MSON content. */
    protected $description = null;

    /** @var array<string, string> Array of enumerated values from the MSON content. */
    protected $values = [];

    /** @var bool Allow all kind of subtypes. Used for `@api-error` annotations to allow error codes. */
    protected $allow_all_subtypes = false;

    /**
     * @param string $class
     * @param string $method
     * @param Config $config
     */
    public function __construct(string $class, string $method, Config $config)
    {
        $this->class = $class;
        $this->method = $method;
        $this->config = $config;
    }

    /**
     * Given a piece of Mill-flavored MSON content, parse it out.
     *
     * @param string $content
     * @return MSON
     * @throws ImproperlyWrittenEnumException
     * @throws MissingOptionsException
     * @throws UnknownErrorRepresentationException
     * @throws UnsupportedTypeException
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
            if (!in_array(strtolower($this->type), self::SUPPORTED_TYPES)) {
                try {
                    // If we're allowing all subtypes, then we're dealing with error states and the `@api-error`
                    // annotation, so we should look at error representations instead here.
                    if ($this->allow_all_subtypes) {
                        $this->config->doesErrorRepresentationExist($this->type);
                    } else {
                        // If this isn't a valid representation, then it's an invalid type.
                        $this->config->doesRepresentationExist($this->type);
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
                        if (!in_array(strtolower($this->subtype), self::SUPPORTED_TYPES)) {
                            try {
                                // If this isn't a valid representation, then it's an invalid type.
                                $this->config->doesRepresentationExist($this->subtype);
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

        if (empty($this->values)) {
            if ($this->type === 'enum') {
                throw MissingOptionsException::create($this->type, $this->class, $this->method);
            } elseif ($this->subtype === 'enum') {
                throw MissingOptionsException::create($this->subtype, $this->class, $this->method);
            }
        }

        if (($this->type !== 'enum' && $this->subtype !== 'enum') && !empty($this->values)) {
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
            $description = preg_replace(self::REGEX_CLEAN_MULTILINE, ' ', trim($descriptions[$k]));

            $enum[$value] = $description;
        }

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
     * {{@inheritdoc}}
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
