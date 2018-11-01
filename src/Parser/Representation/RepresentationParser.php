<?php
namespace Mill\Parser\Representation;

use gossi\docblock\tags\UnknownTag;
use Mill\Container;
use Mill\Exceptions\MethodNotImplementedException;
use Mill\Exceptions\MethodNotSuppliedException;
use Mill\Exceptions\Representation\DuplicateFieldException;
use Mill\Parser;
use Mill\Parser\Annotations\DataAnnotation;
use Mill\Parser\Annotations\ScopeAnnotation;
use Mill\Parser\Version;

class RepresentationParser extends Parser
{
    /**
     * @link http://stackoverflow.com/a/13114141/1886079
     * @var string
     */
    const DOC_PATTERN = '~/\*\*(.*?)\*/~s';

    /** @var string Everything between the second asterisk and the first annotation */
    const DOC_BODY_MATCH = '~\s*\*\s+(.*)~';

    /**
     * Locate, and parse, the annotations for a representation method.
     *
     * @param string|null $method_name
     * @return array
     * @throws DuplicateFieldException
     * @throws MethodNotSuppliedException
     */
    public function getAnnotations(string $method_name = null): array
    {
        if (empty($method_name)) {
            throw MethodNotSuppliedException::create($this->class);
        }

        /** @var string method */
        $this->method = $method_name;

        $reader = Container::getRepresentationAnnotationReader();
        $code = $reader($this->class, $this->method);

        $annotations = $this->parse($code);

        if (count($annotations) > 1) {
            ksort($annotations);

            // Run through all created annotations and cascade any versioning down into any present child annotations.
            /** @var DataAnnotation $annotation */
            foreach ($annotations as $identifier => $annotation) {
                if (!$annotation->getVersion() && !$annotation->getVendorTags() && !$annotation->getScopes()) {
                    continue;
                }

                $this->carryAnnotationSettingsToChildren($annotation, $annotations);
            }
        }

        return $annotations;
    }

    /**
     * Parse a group of our custom annotations.
     *
     * @param array $tags
     * @param string $original_content
     * @return array
     * @throws DuplicateFieldException
     * @throws MethodNotSuppliedException
     * @throws \Mill\Exceptions\Version\UnrecognizedSchemaException
     */
    public function parseAnnotations(array $tags, string $original_content): array
    {
        $scopes = [];
        $see_pointers = [];
        $annotations = [];
        $data = [];

        /** @var Version|null $version */
        $version = null;

        /** @var string $method */
        $method = $this->method;

        // Does this have any `@api-see` pointers or a `@api-version` declaration?
        /** @var UnknownTag $tag */
        foreach ($tags as $tag) {
            $annotation = $this->getAnnotationNameFromTag($tag);
            $content = $tag->getDescription();
            $content = trim($content);

            switch ($annotation) {
                case 'data':
                    $data[] = $content;
                    break;

                case 'scope':
                    $scopes[] = (new ScopeAnnotation($this->application, $content, $this->class, $method))->process();
                    break;

                case 'see':
                    $see_pointers[] = explode(' ', $content);
                    break;

                case 'version':
                    $version = new Version($content, $this->class, $method);
                    break;
            }
        }

        foreach ($data as $content) {
            $annotation = new DataAnnotation($this->application, $content, $this->class, $method, $version);
            $annotation->process();
            if (!empty($scopes)) {
                $annotation->setScopes($scopes);
            }

            $annotations[$annotation->getIdentifier()] = $annotation;
        }

        // If we matched an `@api-see` annotation, then let's parse it out into viable annotations.
        if (!empty($see_pointers)) {
            foreach ($see_pointers as $pointer) {
                list($see_class, $see_method) = explode('::', array_shift($pointer));
                if (in_array(strtolower($see_class), ['self', 'static'])) {
                    $see_class = $this->class;
                }

                $prefix = array_shift($pointer);

                // Pass in the current array (by reference) of found annotations that we have so we can do depth
                // traversal for version and  requirements of any implied children, by way of dot-notation.
                $parser = new self($see_class, $this->application);
                $see_annotations = $parser->getAnnotations($see_method);

                /** @var DataAnnotation $annotation */
                foreach ($see_annotations as $name => $annotation) {
                    // If this `@api-see` is being used with an `@api-version`, then the version here should always be
                    // applied to any annotations we're including with the `@api-see`.
                    //
                    // If, however, an annotation we're loading has its own versioning set, we'll combine the pointers
                    // version with the annotations version to create a new constraint specifically for that annotation.
                    //
                    // For example, if `external_urls` is versioned at `>=1.1`, and points to a method to load
                    // `external_urls.tickets`, but that's versioned at `<1.1.3`, the new parsed constraint for
                    // `external_urls.tickets` will be `>=1.1 <1.1.3`.
                    if ($version) {
                        $annotation_version = $annotation->getVersion();
                        if ($annotation_version) {
                            $new_constraint = implode(' ', [
                                $version->getConstraint(),
                                $annotation_version->getConstraint()
                            ]);

                            $updated_version = new Version($new_constraint, $this->class, $method);
                            $annotation->setVersion($updated_version);
                        } else {
                            $annotation->setVersion($version);
                        }
                    }

                    // If this `@api-see` is being used with `@api-scope` annotations, the scope should filter down
                    // the pipe.
                    if (!empty($scopes)) {
                        $annotation->setScopes($scopes);
                    }

                    // If this `@api-see` has a prefix to attach to found annotation identifiers, do so.
                    if (!empty($prefix)) {
                        $see_annotations[$prefix . '.' . $name] = $annotation->setIdentifierPrefix($prefix);
                        unset($see_annotations[$name]);
                    }
                }

                $annotations += $see_annotations;
            }
        }

        return $annotations;
    }

    /**
     * Parse a block of code for representation documentation and return an array of annotations.
     *
     * @param string $code
     * @return array
     * @throws DuplicateFieldException
     * @throws \Mill\Exceptions\Resource\UnsupportedDecoratorException
     */
    public function parse(string $code): array
    {
        $representation = [];

        if (preg_match_all(self::DOC_PATTERN, $code, $matches)) {
            foreach ($matches[1] as $block) {
                $annotations = $this->parseDocblock($block, false);
                if (empty($annotations)) {
                    continue;
                }

                foreach ($annotations as $field_name => $annotation) {
                    if (isset($representation[$field_name])) {
                        /** @var string $method */
                        $method = $this->method;
                        throw DuplicateFieldException::create($field_name, $this->class, $method);
                    }

                    $representation[$field_name] = $annotations[$field_name];
                }
            }
        }

        return $representation;
    }

    /**
     * Given a DataAnnotation object, carry any versioning or vendor tags on it down to any dot-notation children in
     * an array of other annotations.
     *
     * @param DataAnnotation $parent
     * @param array $annotations
     */
    private function carryAnnotationSettingsToChildren(DataAnnotation $parent, array &$annotations = []): void
    {
        $parent_identifier = $parent->getIdentifier();
        $parent_version = $parent->getVersion();
        $parent_vendor_tags = $parent->getVendorTags();

        /** @var array<ScopeAnnotation> $parent_scopes */
        $parent_scopes = $parent->getScopes();

        /** @var DataAnnotation $annotation */
        foreach ($annotations as $identifier => $annotation) {
            if ($identifier === $parent_identifier) {
                continue;
            }

            // Is this annotation a child of what we're looking for?
            if ($parent_identifier . '.' !== substr($identifier, 0, strlen($parent_identifier . '.'))) {
                continue;
            }

            if (!empty($parent_version) && !$annotation->getVersion()) {
                $annotation->setVersion($parent_version);
            }

            if (!empty($parent_vendor_tags) && !$annotation->getVendorTags()) {
                $annotation->setVendorTags($parent_vendor_tags);
            }

            if (!empty($parent_scopes) && !$annotation->getScopes()) {
                $annotation->setScopes($parent_scopes);
            }
        }
    }
}
