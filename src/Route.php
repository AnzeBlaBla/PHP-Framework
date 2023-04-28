<?php

namespace AnzeBlaBla\Framework;

class Route
{
    private $urlPath = null;
    private $fileSystemPath = null;
    private ?FileSystemRouter $router = null;

    public function __construct($path, $router)
    {
        $this->fileSystemPath = $router->rootFilesystemPath . '/' . $path;
        $this->urlPath = $path;
        $this->router = $router;
    }

    public function matches($uri)
    {
        //echo $this->urlPath . ' == ' . $uri . '<br>';
        return $this->urlPath == $uri;
    }

    public function render()
    {
        //return "Route: " . $this->fileSystemPath;
        
        //$framework = $this->router->framework;

        $component = new Component(require($this->fileSystemPath), $this->router->framework->getHelpers(), $props, $key);

        //print_r($component);

        return $component->render();
    }

    /* Default string conversion */
    public function __toString()
    {
        return $this->render();
    }
}
