<?php
namespace Mill\Parser\Annotations\Traits;

/**
 * Handler for annotations that have an HTTP coded response.
 *
 */
trait HasHttpCodeResponseTrait
{
    /**
     * HTTP code that this response throws.
     *
     * @var string
     */
    protected $http_code;

    /**
     * Name of the representation that this annotation responds with. Can be either a fully qualified class name, or
     * `string`.
     *
     * @var string
     */
    protected $representation;

    /**
     * Mapping array of HTTP code to the string that that code represents.
     *
     * @var array
     */
    private static $http_codes = [
        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // 3xx Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // 4xx Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        // 5xx Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];

    /**
     * Get the HTTP code that this response throws.
     *
     * @return string
     */
    public function getHttpCode()
    {
        return $this->http_code;
    }

    /**
     * Get the HTTP message for a specific HTTP code.
     *
     * @param string|integer $http_code
     * @return string
     */
    public function getHttpCodeMessage($http_code)
    {
        return self::$http_codes[$http_code];
    }

    /**
     * Is a given HTTP code valid?
     *
     * @param string $http_code
     * @return bool
     */
    public function isValidHttpCode($http_code)
    {
        return isset(self::$http_codes[$http_code]);
    }

    /**
     * Get the representation that this response returns data in.
     *
     * @return string
     */
    public function getRepresentation()
    {
        return $this->representation;
    }
}
