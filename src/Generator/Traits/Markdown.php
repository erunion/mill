<?php
namespace Mill\Generator\Traits;

trait Markdown
{
    /**
     * Return a repeated new line character.
     *
     * @param integer $repeat
     * @return string
     */
    protected function line($repeat = 1)
    {
        return str_repeat("\n", $repeat);
    }

    /**
     * Return a repeated tab character.
     *
     * @param integer $repeat
     * @return string
     */
    protected function tab($repeat = 1)
    {
        return str_repeat('    ', $repeat);
    }
}
