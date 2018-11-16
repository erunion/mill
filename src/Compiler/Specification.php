<?php
namespace Mill\Compiler;

use Mill\Compiler;

class Specification extends Compiler
{
    use Compiler\Traits\Markdown;

    /** @var array */
    protected $specifications = [];

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
