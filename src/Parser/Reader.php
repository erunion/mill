<?php
namespace Mill\Parser;

//use Mill\Exceptions\MethodNotImplementedException;
//use ReflectionClass;
use Mill\Parser\Reader\Docblock;

/**
 * Class for loading a class (or class method) and reading its docblock.
 *
 */
class Reader
{
    /**
     * @link https://gist.github.com/asika32764/9268066
     */
    const DOCBLOCK_REGEX = '/\s?(\/\*(?:[^*]|\n|(?:\*(?:[^\/]|\n)))*\*\/)/uis';

    /**
     * Load, and pull, the annotations on a file.
     *
     * @param string $file
     * @return array<Docblock>
     */
    public function getAnnotations(string $file): array
    {
        $comments = [];
        $line_start = 1;
        $docblock = '';

        $content = $this->readFile($file);
        $content = explode("\n", $content);
        foreach ($content as $line_number => $line) {
            if (strpos($line, '/**') === false && strpos($line, '*') === false) {
                $docblock = '';
                $line_start = $line_number;
                continue;
            }

            $docblock .= $line . "\n";
            preg_match(self::DOCBLOCK_REGEX, $docblock, $matches);
            if (empty($matches)) {
                continue;
            } elseif (strpos($docblock, '@api-') === false) {
                continue;
            }

            $comments[] = new Docblock(
                $matches[1],
                $file,
                $line_start + 2,
                ++$line_number
            );
        }

        return $comments;
    }

    /**
     * Given a class and method, pull out any code annotation docblocks that may exist within it.
     *
     * @param string $class
     * @param string $method
     * @return string
     * @throws MethodNotImplementedException If the supplied method does not exist on the supplied class.
     */
    /*public function getRepresentationAnnotations(string $class, string $method): string
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->hasMethod($method)) {
            throw MethodNotImplementedException::create($class, $method);
        }

        // @var \ReflectionMethod $method
        $method = $reflection->getMethod($method);

        // @var string $filename
        $filename = $method->getFileName();

        // The start line is actually `- 1`, otherwise you wont get the function() block.
        $start_line = $method->getStartLine() - 1;
        $end_line = $method->getEndLine();
        $length = $end_line - $start_line;

        // @var array $source
        $source = file($filename);

        return implode('', array_slice($source, $start_line, $length));
    }*/

    protected function readFile(string $file): string
    {
        return file_get_contents($file);
    }
}
