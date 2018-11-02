<?php
namespace Mill\Parser\Annotations;

use Mill\Container;
use Mill\Parser\MSON;

class PathParamAnnotation extends ParamAnnotation
{
    const REQUIRES_VISIBILITY_DECORATOR = false;
    const SUPPORTS_DEPRECATION = false;
    const SUPPORTS_MSON = true;
    const SUPPORTS_VENDOR_TAGS = false;
    const SUPPORTS_VERSIONING = false;

    const PAYLOAD_FORMAT = 'path';

    const ARRAYABLE = [
        'description',
        'field',
        'required',
        'sample_data',
        'type',
        'values'
    ];

    /**
     * {@inheritdoc}
     */
    protected function parser(): array
    {
        $config = $this->application->getConfig();
        $content = trim($this->docblock);

        /** @var string $method */
        $method = $this->method;
        $mson = (new MSON($this->class, $method, $config))->parse($content);
        $parsed = [
            'field' => $mson->getField(),
            'sample_data' => $mson->getSampleData(),
            'type' => $mson->getType(),
            'description' => $mson->getDescription(),
            'values' => $mson->getValues()
        ];

        if (!empty($parsed['field'])) {
            // If we have any path param translations configured, let's process them.
            $translations = $config->getPathParamTranslations();
            if (isset($translations[$parsed['field']])) {
                $parsed['field'] = $translations[$parsed['field']];
            }
        }

        return $parsed;
    }

    /**
     * {@inheritdoc}
     */
    protected function interpreter(): void
    {
        $this->required = true;

        $this->field = $this->required('field');
        $this->sample_data = $this->optional('sample_data');
        $this->type = $this->required('type');
        $this->description = $this->required('description');

        $this->values = $this->optional('values');
    }
}
