<?php
namespace Mill\Generator\Traits;

use StringTemplate\Engine;

trait TextRendering
{
    /**
     * @var Engine|null
     */
    protected $template_engine;

    /**
     * Render a template with some content.
     *
     * @param string $template
     * @param array $content
     * @return string
     */
    protected function renderText($template, array $content = [])
    {
        if (is_null($this->template_engine)) {
            $this->template_engine = new Engine;
        }

        return $this->template_engine->render($template, $content);
    }

    /**
     * Join an array of words into a structure for use in a sentence.
     *
     *  - [word1, word2] -> "word1 and word 2"
     *  - [word1, word2, word3] -> "word1, word2 and word 3"
     *
     * @param array $words
     * @return string
     */
    protected function joinWords(array $words)
    {
        if (count($words) <= 2) {
            return implode(' and ', $words);
        }

        $last = array_pop($words);
        return implode(', ', $words) . ' and ' . $last;
    }
}
