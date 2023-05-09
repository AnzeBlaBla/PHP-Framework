<?php

namespace AnzeBlaBla\Framework;

class Route extends RouteItem
{
    
    public Component $component;    

    public function __construct(string $path, FileSystemRouter $router)
    {
        //echo "Route construct: $path<br>";
        parent::__construct($path, $router);

        if ($path == '') {
            throw new \Exception('Route path cannot be empty');
        }

        $componentPath = $router->componentsRootPath . '/' . $path;
        
        // Remove php
        if (substr($componentPath, -4) == '.php') {
            $componentPath = substr($componentPath, 0, -4);
        }

        
        $this->component = new Component($componentPath, $router->framework);

    }

    private string $renderedUrl;
    private array $renderedQuery;

    public function render(string $url, $query = array(), string|array $content = null)
    {
        $this->renderedUrl = $url;
        $this->renderedQuery = $query;

        //echo "Rendering route: " . $this->path . " (" . $this->urlPath . ") for url: " . $url . "<br>";

        $props = [
            'query' => $query,
            'router' => $this->router,
            'route' => $this->router->currentRoute,
        ];
 
        // For layouts
        if ($content != null) {
            $props['content'] = $content;
        }

        $this->component->setProps($props);

        $renderedComponent = $this->component->render();

        return $renderedComponent;
    }

    private bool $rewritten = false;
    /**
     * Rewrite this route to a new one
     * @param string $newUrl
     */
    public function rewrite(string $newUrl)
    {
        $newRoute = new Route($newUrl, $this->router);
        $this->rewritten = true;

        $this->router->currentRoute = $newRoute;

        return $newRoute->render($this->renderedUrl, $this->renderedQuery);
    }


    /* Default string conversion */
    public function __toString()
    {
        return "Error: Route to string conversion is not possible.";
    }    
}
