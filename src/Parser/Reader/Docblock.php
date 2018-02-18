<?php
namespace Mill\Parser\Reader;

class Docblock
{
    /** @var string */
    protected $docblock;

    /** @var string */
    protected $filename;

    /** @var int */
    protected $line_start;

    /** @var int */
    protected $line_end;

    public function __construct(string $docblock, string $filename, int $line_start, int $line_end)
    {
        $this->docblock = $docblock;
        $this->filename = $filename;
        $this->line_start = $line_start;
        $this->line_end = $line_end;
    }

    /**
     * Parse out annotations from the current docblock.
     *
     * @return \gossi\docblock\Docblock
     */
    public function getAnnotations(): \gossi\docblock\Docblock
    {
        return new \gossi\docblock\Docblock($this->docblock);
    }

    /**
     * @return string
     */
    public function getDocblock(): string
    {
        return $this->docblock;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getLines(): string
    {
        return $this->line_start . '-' . $this->line_end;
    }

    /**
     * @return int
     */
    public function getLineStart(): int
    {
        return $this->line_start;
    }

    /**
     * @return int
     */
    public function getLineEnd(): int
    {
        return $this->line_end;
    }
}
