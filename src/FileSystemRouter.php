<?php

namespace AnzeBlaBla\Framework;

class FileSystemRouter
{
    private string $prefix = '';
    public function setPrefix(string $prefix)
    {
        // make sure to start and end with /
        if (substr($prefix, 0, 1) != '/') {
            $prefix = '/' . $prefix;
        }
        if (substr($prefix, -1) != '/') {
            $prefix .= '/';
        }
        $this->prefix = $prefix;
    }

    public string $ignoreFilePrefix = '_';
    public function setIgnoreFilePrefix(string $prefix)
    {
        $this->ignoreFilePrefix = $prefix;
    }

    public string $layoutFileName = '#layout.php';
    public function setLayoutFileName(string $name)
    {
        $this->layoutFileName = $name;
    }

    public ?string $rootFilesystemPath = null;
    public ?string $componentsRootPath = null;

    /**
     * @var Route[] $routes
     */
    private $routes = array();
    private ?Route $errorRoute = null; // Error route is displayed when no other route matches
    private ?Component $errorComponent = null; // Error component is displayed when no other route matches (inside the appropriate layouts)
    public ?Framework $framework = null;

    /**
     * @param string $path
     * @param Framework $framework
     */
    public function __construct(string $path, Framework $framework)
    {
        // This path is relative to the root component path
        $this->rootFilesystemPath = Utils::fix_path($framework->componentsRoot . $path);
        $this->componentsRootPath = $path;
        $this->framework = $framework;

        // Recurse folder and add to routes array
        $this->routes = $this->getRoutesFromFolder($this->rootFilesystemPath);

    }

    /**
     * Sets the error route (when no other route matches)
     * @param string $path
     */
    public function setErrorRoute($path)
    {
        $this->errorRoute = new Route($path . ".php", $this);
    }

    /**
     * Sets the error component (when no other route matches - displayed inside the appropriate layouts)
     * @param string $componentPath
     */
    public function setErrorComponent(string $componentPath)
    {
        $this->errorComponent = new Component($componentPath, $this->framework);
    }

    /**
     * Router render method
     * @return mixed
     */
    public function render()
    {
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // remove whole prefix
        if (substr($url, 0, strlen($this->prefix)) == $this->prefix) {
            $url = substr($url, strlen($this->prefix));
        // Or if url is just prexix (can be without trailing slash)
        } else if ($url == $this->prefix || $url == substr($this->prefix, 0, -1)) {
            $url = ''; // empty string (root
        } else {
            return null;
        }
        // URL needs to start with /
        if (substr($url, 0, 1) != '/') {
            $url = '/' . $url;
        }
        
        $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '';
        parse_str($queryString, $query); // Parse query string into array

        $routeToRender = $this->findRoute($url);

        if ($routeToRender == null) {
            //echo "<h1>Printing error</h1>";
            //$routeToRender = $this->errorRoute;

            // Both error component and error route cannot be set
            if ($this->errorComponent != null && $this->errorRoute != null) {
                throw new \Exception("Only use setErrorComponent or setErrorRoute, not both");
            }

            // If error component is set, use that
            if ($this->errorComponent != null) {
                // Render error component with the correct layouts
                throw new \Exception("Error components not implemented yet!");
            } else if ($this->errorRoute != null) {
                $routeToRender = $this->errorRoute;
            } else {
                throw new \Exception("No route found for URL: " . $url);
            }
        }

        return $routeToRender->render($url, $query);
    }

    /**
     * @return string
     */
    public function renderAPI()
    {
        $rendered = $this->render();
        if ($rendered) {
            header('Content-Type: application/json');
            echo json_encode($rendered);
            die;
        }
    }

    /**
     * @param string $url
     * @return Route|null
     */
    public function findRoute($url): ?Route
    {        
        $tryURLs = [$url];

        /* // if ends with /, remove it
        if (substr($url, -1) == '/') {
            $tryURLs[] = substr($url, 0, -1);
        }

        // if does not end with /, add it
        if (substr($url, -1) != '/') {
            $tryURLs[] = $url . '/';
        } */

        $matches = array();

        foreach ($tryURLs as $tryURL) {
            foreach ($this->routes as $route) {
                if ($route->matches($tryURL)) {
                    $matches[] = $route;
                }
            }
        }

        if (count($matches) > 1) {
            
            // If there is only one non-dynamic, use that one
            /**
             * @var Route[] $nonDynamicMatches
             * @var Route[] $matches
             * @param Route $route
            */
            
            $nonDynamicMatches = array_values(array_filter($matches, function ($route) {
                return !$route->isDynamic();
            }));

            if (count($nonDynamicMatches) == 1) {
                return $nonDynamicMatches[0];
            }

            throw new \Exception("Multiple routes match the URL: " . $url . " (" . count($matches) . " matches)");
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


    /**
     * @param string $path
     * @return Route[]
     */
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

            // Ignore all files starting with ignoreFilePrefix
            if (substr($file, 0, strlen($this->ignoreFilePrefix)) == $this->ignoreFilePrefix) {
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
