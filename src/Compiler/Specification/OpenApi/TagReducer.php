<?php
namespace Mill\Compiler\Specification\OpenApi;

class TagReducer
{
    /** @var array */
    protected $specification;

    /**
     * @param array $specification
     */
    public function __construct(array $specification)
    {
        $this->specification = $specification;
    }

    /**
     * @return array
     */
    public function reduce(): array
    {
        $tagged_specs = [];
        foreach ($this->specification['tags'] as $tag) {
            $tag = $tag['name'];

            $tagged_specs[$tag] = $this->reduceForTag($tag, $this->specification);
        }

        return $tagged_specs;
    }

    /**
     * @param string $tag
     * @param array $specification
     * @return array
     */
    public function reduceForTag(string $tag, array $specification): array
    {
        // Filter tags down to just what we're looking for.
        $specification['tags'] = array_filter(
            $specification['tags'],
            function (array $spec_tag) use ($tag): bool {
                return $spec_tag['name'] === $tag;
            }
        );

        sort($specification['tags']);

        // Search component schemas and construct a linked-list of refs.
        $linked_refs = [];
        $schemas = $specification['components']['schemas'];
        foreach ($schemas as $name => $schema) {
            $linked_refs[$name] = $this->getSchemaRefsFromResource($schema);
        }

        // Filter paths down to just those that contain the tag we're looking for.
        $path_refs = [];
        $paths = $specification['paths'];
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $schema) {
                if (!in_array($tag, $schema['tags'])) {
                    unset($paths[$path][$method]);
                    continue;
                }

                // Locate all used components so we can eliminate ones that aren't utilized from the filtered
                // specification.
                $path_refs = $this->getSchemaRefsFromResource($schema, $path_refs);
            }

            if (empty($paths[$path])) {
                unset($paths[$path]);
            }
        }

        $specification['paths'] = $paths;

        // Combine the path refs with the linked refs and see what refs we can filter to.
        $refs = [];
        foreach ($path_refs as $ref) {
            $refs[] = $ref;
            if (!empty($linked_refs[$ref])) {
                $refs = array_merge($refs, $linked_refs[$ref]);
            }
        }

        $refs = array_unique($refs);

        // Filter down component schemas to just what we need for this tag.
        foreach ($specification['components']['schemas'] as $name => $schema) {
            if (!in_array($name, $refs)) {
                unset($specification['components']['schemas'][$name]);
            }
        }

        return $specification;
    }

    /**
     * @param array $resource
     * @param array $refs
     * @return array
     */
    protected function getSchemaRefsFromResource(array $resource, array $refs = []): array
    {
        foreach ($resource as $k => $v) {
            if ($k === '$ref') {
                $ref = str_replace('#/components/schemas/', '', $v);
                if (!in_array($ref, $refs)) {
                    $refs[] = $ref;
                }
            } elseif (is_array($v)) {
                $refs = $this->getSchemaRefsFromResource($v, $refs);
            }
        }

        return $refs;
    }
}
