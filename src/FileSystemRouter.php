<?php

namespace AnzeBlaBla\Framework;

class FileSystemRouter
{
    public $rootPath = null;
    public $rootFilesystemPath = null;

    /**
     * @var Route[] $routes
     */
    private $routes = array();
    private $errorRoute = null;
    public ?Framework $framework = null;

    public function __construct($path, $framework)
    {
        // This path is relative to the root component path
        $this->rootPath = $path;
        $this->rootFilesystemPath = $framework->componentsRoot . $path;
        $this->framework = $framework;

        //print_r($this->rootFilesystemPath);

        // Recurse folder and add to routes array
        $this->routes = $this->getRoutesFromFolder($this->rootFilesystemPath);

        /* echo "<pre>";
        print_r($this->routes);
        echo "</pre>"; */
    }

    public function setErrorRoute($path)
    {
        $this->errorRoute = new Route($path . ".php", $this);
    }

    public function render()
    {
        $routeToRender = $this->findRoute($_SERVER['REQUEST_URI']);

        if ($routeToRender == null && $this->errorRoute != null) {
            //echo "<h1>Printing error</h1>";
            $routeToRender = $this->errorRoute;
        }

        return $routeToRender->render();
    }

    public function findRoute($uri)
    {
        // split away query string
        $parts = explode('?', $uri);
        $uri = $parts[0];
        $qs = $parts[1] ?? null;

        $tryURIs = [$uri];

        // if doesnt end with php (and not with /), add a /index.php
        if (substr($uri, -4) != '.php' && substr($uri, -1) != '/') {
            $tryURIs[] = $uri . '/index.php';
        }

        // if doesnt end with php, add a .php
        if (substr($uri, -4) != '.php') {
            $tryURIs[] = $uri . '.php';
        }

        // if ends with / add index.php
        if (substr($uri, -1) == '/') {
            $tryURIs[] = $uri . 'index.php';
        }

        $matches = array();

        foreach ($tryURIs as $tryURI) {
            foreach ($this->routes as $route) {
                if ($route->matches($tryURI)) {
                    $matches[] = $route;
                }
            }
        }

        if (count($matches) > 1) {
            throw new \Exception("Multiple routes match the URI: " . $uri);
        }

        if (count($matches) == 1) {
            return $matches[0];
        }

        return null;
    }

    /* Default string conversion */
    public function __toString()
    {
        return $this->render();
    }


    public function getRoutesFromFolder($path)
    {
        $relativePath = substr($path, strlen($this->rootFilesystemPath));
        $routes = array();

        // Get all files and folders in the path
        $files = scandir($path);

        // Loop through all files and folders
        foreach ($files as $file) {
            // Skip . and ..
            if ($file == "." || $file == "..") {
                continue;
            }

            // If it's a folder, recurse
            if (is_dir($path . "/" . $file)) {
                $routes = array_merge($routes, $this->getRoutesFromFolder($path . "/" . $file));
            } else {
                // If it's a file, add it to the routes array
                $routes[] = new Route($relativePath . "/" . $file, $this);
            }
        }

        return $routes;
    }
}
