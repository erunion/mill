<?php
namespace Mill\Compiler\Traits;

trait Markdown
{
    /**
     * Return a repeated new line character.
     *
     * @param int $repeat
     * @return string
     */
    protected function line(int $repeat = 1): string
    {
        return str_repeat("\n", $repeat);
    }

    /**
     * Return a repeated tab character.
     *
     * @param int $repeat
     * @return string
     */
    protected function tab(int $repeat = 1): string
    {
        return str_repeat('    ', $repeat);
    }
}
