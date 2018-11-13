<?php
namespace Mill\Compiler;

use Mill\Compiler;

class Specification extends Compiler
{
    use Compiler\Traits\Markdown;

    /** @var array Current list of representations for the current API version we're working with. */
    protected $representations = [];

    /** @var array */
    protected $specifications = [];

    /**
     * Pull a representation from the current versioned set of representations.
     *
     * @param string $representation
     * @return false|\Mill\Parser\Representation\Documentation
     */
    protected function getRepresentation(string $representation)
    {
        return (isset($this->representations[$representation])) ? $this->representations[$representation] : false;
    }

    /**
     * @return array
     */
    public function getCompiled(): array
    {
        if (empty($this->specifications)) {
            $this->compile();
        }

        return $this->specifications;
    }

    /**
     * Convert an MSON sample data into a piece of data for that appropriate field type.
     *
     * @param bool|string $data
     * @param string $type
     * @return bool|string
     */
    protected function convertSampleDataToCompatibleDataType($data, string $type)
    {
        if ($type === 'boolean') {
            if ($data === '0') {
                return 'false';
            } elseif ($data === '1') {
                return 'true';
            }
        }

        return $data;
    }
}
