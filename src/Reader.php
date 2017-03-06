<?php
namespace Mill;

use Mill\Exceptions\MethodNotImplementedException;
use ReflectionClass;

/**
 * Class for loading a class (or class method) and reading its docblock.
 *
 */
class Reader
{
    /**
     * Load, and pull, the annotations for a class or class method.
     *
     * @param string $class
     * @param string|null $method
     * @return string|false The found annotation docblock.
     * @throws MethodNotImplementedException If the supplied method does not exist on the supplied class.
     */
    public function getAnnotations($class, $method = null)
    {
        $reflection = new ReflectionClass($class);

        if (empty($method)) {
            $comments = $reflection->getDocComment();
        } else {
            if (!$reflection->hasMethod($method)) {
                throw MethodNotImplementedException::create($class, $method);
            }

            /** @var \ReflectionMethod $method */
            $method = $reflection->getMethod($method);
            $comments = $method->getDocComment();
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
    public function getRepresentationAnnotations($class, $method)
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->hasMethod($method)) {
            throw MethodNotImplementedException::create($class, $method);
        }

        /** @var \ReflectionMethod $method */
        $method = $reflection->getMethod($method);

        /** @var string $filename */
        $filename = $method->getFileName();

        // The start line is actually `- 1`, otherwise you wont get the function() block.
        $start_line = $method->getStartLine() - 1;
        $end_line = $method->getEndLine();
        $length = $end_line - $start_line;

        /** @var array $source */
        $source = file($filename);

        return implode('', array_slice($source, $start_line, $length));
    }
}
