<?php
namespace Mill;

use Mill\Exceptions\BaseException;
use Mill\Exceptions\Config\UnconfiguredRepresentationException;
use Mill\Parser\Annotations\RepresentationAnnotation;

class Application
{
    /** @var Config */
    protected $config;

    /** @var array */
    protected $controllers = [];

    /** @var array */
    protected $preloaded = [
        'representations' => []
    ];

    /** @var array */
    protected $representations = [];

    /** @var bool */
    protected $trigger_errors = true;

    /** @var array */
    protected $triggered_errors = [];

    /**
     * Application constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Quickly scan and preload all representations by their defined name, `@api-resource`, so when we later do a full
     * application scan, we can link called out representations in `@api-data`, `@api-return` and `@api-throws`
     * annotations.
     *
     * @throws \Exception
     */
    public function preload(): void
    {
        // Preload representations
        foreach ($this->config->getRepresentations() as $representation) {
            $this->preloadRepresentation($representation);
        }

        // Preload error representations
        foreach ($this->config->getErrorRepresentations() as $representation) {
            $this->preloadRepresentation($representation['filename']);
        }
    }

    /**
     * @param string $representation
     * @throws \Exception
     */
    private function preloadRepresentation(string $representation): void
    {
        /** @var RepresentationAnnotation $annotation */
        $annotation = (new Parser($this, $representation))->getAnnotation('representation');
        $name = $annotation->getName();
        if (isset($this->preloaded['representations'][$name])) {
            /** @var RepresentationAnnotation $representation */
            $representation = $this->preloaded['representations'][$name];
            throw new \Exception(sprintf(
                'The `@api-representation %s` annotation in `%s` is not valid as another representation with ' .
                    'the name of `%s` already exists within `%s`.',
                $name,
                $representation,
                $name,
                $representation->getDocblock()->getFilename()
            ));
        }

        $this->preloaded['representations'][$name] = $annotation->getDocblock()->getFilename();
    }

    /*public function loadRepresentation(string $file): bool
    {
        if (!$this->config->hasRepresentation($file)) {
            return false;
        }

        $this->representations[$file] = (new Parser($this, $file))->getAnnotations();

print_r([
    'ha sit',
    $file,
    $this->representations[$file]
]);exit;
    }*/

    /**
     * Check if a specific representation exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasRepresentation(string $name): bool
    {
        if (isset($this->preloaded['representations'][$name]) || isset($this->representations[$name])) {
            return true;
        }

        return false;
    }

    public function trigger(BaseException $exception): void
    {
        if ($this->trigger_errors) {
            throw $exception;
        }

        $this->triggered_errors[] = $exception;
    }

    public function triggerErrors($throw = true): self
    {
        $this->trigger_errors = $throw;
        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
