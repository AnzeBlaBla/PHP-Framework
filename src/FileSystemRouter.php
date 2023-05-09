<?php

namespace AnzeBlaBla\Framework;

class FileSystemRouter
{
    /**
     * URL prefix, starting with / and ending with /
     */
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

        return $this;
    }
    /**
     * Prefix for files that should be ignored (default: '_')
     */
    public string $ignoreFilePrefix = '_';
    public function setIgnoreFilePrefix(string $prefix)
    {
        $this->ignoreFilePrefix = $prefix;

        return $this;
    }

    /**
     * Layout route name (default: '#layout')
     */
    public string $layoutName = '#layout';
    public function setLayoutName(string $name)
    {
        $this->layoutName = $name;

        return $this;
    }

    /**
     * Index file name (default: 'index.php')
     */
    public string $indexRoute = 'index';
    public function setIndexRoute(string $name)
    {
        $this->indexRoute = $name;

        return $this;
    }

    public ?string $rootFilesystemPath = null;
    public ?string $componentsRootPath = null;

    /**
     * Recursive array of routes
     */
    private $routes = array();
    private ?Route $errorRoute = null; // Error route is displayed when no other route matches
    public ?Framework $framework = null;

    public Route $currentRoute; // Current route is the route that is currently being rendered (the deepest route that matches the url)

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

        //Utils::debug_print($this->routes);
    }

    /**
     * Sets the error route (when no other route matches)
     * @param string $path
     * @return \AnzeBlaBla\Framework\FileSystemRouter
     */
    public function setErrorRoute($path)
    {
        $this->errorRoute = $this->findRoute($path);

        return $this;
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
            // If url does not start with prefix, do not render
            return null;
        }
        // URL needs to start with /
        if (substr($url, 0, 1) != '/') {
            $url = '/' . $url;
        }

        $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '';
        parse_str($queryString, $query); // Parse query string into array

        //Utils::debug_print("Rendering URL: ", $url, " - Query: ", $query);

        /**
         * @var Route[] $layouts
         */
        $layouts = [];
        $routeToRender = $this->findRoute($url, $query, $layouts);
        // Reverse layouts
        $layouts = array_reverse($layouts);

        //Utils::debug_print("Route to render: ", $routeToRender);

        if ($routeToRender == null) {
            throw new \Exception("No route found for URL: " . $url);
        }
        $this->currentRoute = $routeToRender;

        $renderedRoute = $routeToRender->render($url, $query);
        // Apply layouts
        foreach ($layouts as $layout) {
            $renderedRoute = $layout->render($url, $query, $renderedRoute);
        }

        return $renderedRoute;
    }

    /**
     * @return string
     */
    public function runAPI()
    {
        // Make sure framework no data has been sent yet
        if (ob_get_contents()) {
            throw new \Exception("Cannot run API, data has already been sent (make sure to run API before rendering the framework)");
        }

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
    public function findRoute(string $url, array &$query = [], array &$layouts = []): ?Route
    {
        /* // if ends with /, remove it
        if (substr($url, -1) == '/') {
            $tryURLs[] = substr($url, 0, -1);
        }

        // if does not end with /, add it
        if (substr($url, -1) != '/') {
            $tryURLs[] = $url . '/';
        } */

        // URL decode the url
        $url = urldecode($url);

        $urlParts = explode('/', $url);
        // Remove first empty part
        if ($urlParts[0] == '') {
            array_shift($urlParts);
        }
        // if path is / make it empty array
        if (count($urlParts) == 1 && $urlParts[0] == '') {
            $urlParts = array();
        }
        //Utils::debug_print("Finding route for url: ", $url);
        $foundRoute = $this->recursiveFindRoute($urlParts, $this->routes, $query, $layouts);
        //Utils::debug_print("Found route 1: " . $foundRoute->path, $query);
        // If route folder, try to return index
        if ($foundRoute instanceof RouteFolder) {
            //Utils::debug_print("Found route item: ", $foundRoute);
            $foundRoute = $foundRoute->getItem($this->indexRoute);
        }

        //Utils::debug_print("Found route: ", $foundRoute);

        // Error if null
        if ($foundRoute == null) {
            //Utils::debug_print("No route found for url: ", $url);
            if ($this->errorRoute) {
                //Utils::debug_print("Returning error route: ", $this->errorRoute);
                return $this->errorRoute;
            } else {
                return null;
            }
        }
        //Utils::debug_print("Found route 2: " . $foundRoute->path, $query);


        return $foundRoute;
    }

    /**
     * @param string $url
     * @param RouteItem $routes
     * @return Route|null
     */
    public function recursiveFindRoute(array $urlParts, ?RouteItem $routeItem, array &$query, array &$layouts): ?RouteItem
    {
        if ($routeItem instanceof RouteFolder) {
            if($routeItem->hasLayout)
            {
                $layouts[] = $routeItem->getLayout();
            }
        }
        // If there are more url parts, recurse
        if (count($urlParts) > 0) {
            // Must be a route folder
            if (!$routeItem instanceof RouteFolder) {
                return null;
            }

            $firstURLPart = $urlParts[0];

            // If there is a route with this name
            $subItem = $routeItem->getItem($firstURLPart);
            //Utils::debug_print("GotItem:", $routeItem, ":GotItem:", $urlParts, ":2GotItem:", $subItem, ":GotItem");

            if (!$subItem) {
                // If there is a dynamic route
                if ($routeItem->dynamicRoute) {
                    //Utils::debug_print("Using dynamic route: ", $routeItem->dynamicRoute);
                    // add to query
                    $dynamicRouteNoBrackets = substr($routeItem->dynamicRoute, 1, -1);
                    $query[$dynamicRouteNoBrackets] = $firstURLPart;

                    $subItem = $routeItem->getDynamicRoute();
                }
            }

            //Utils::debug_print("Subitem:", $subItem, ":Subitem");

            return $this->recursiveFindRoute(array_slice($urlParts, 1), $subItem, $query, $layouts);
        } else {
            //Utils::debug_print("Returning route item: ", $routeItem);
            return $routeItem;
        }
    }

    /* Default string conversion */
    public function __toString()
    {
        return $this->render();
    }


    /**
     * @param string $path
     * @return RouteItem
     */
    public function getRoutesFromFolder($path)
    {
        $relativePath = substr($path, strlen($this->rootFilesystemPath));
        $routes = new RouteFolder($relativePath, $this);

        //Utils::debug_print("Getting routes from folder: " . $path . " - " . $relativePath);

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
                // If starts with '[' and ends with ']', it's a dynamic route
                $routeItem = $this->getRoutesFromFolder($path . "/" . $file);
                $routes->addItem($file, $routeItem);
            } else { // must be php file
                if (substr($file, -4) != '.php') {
                    continue;
                }
                $fileNoExt = substr($file, 0, -4);
                // If it's a file, add it to the routes array
                $route = new Route($relativePath . "/" . $file, $this);
                $routes->addItem($fileNoExt, $route);
            }
        }

        return $routes;
    }


    /**
     * Helper function for redirecting to a URL (for example for auth protection)
     * @param string $url
     */
    public function redirect($url)
    {
        header("Location: " . $url);
        die;
    }
}
