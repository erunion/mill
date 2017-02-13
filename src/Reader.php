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
}
