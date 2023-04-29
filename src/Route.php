<?php

namespace AnzeBlaBla\Framework;

class Route
{
    private ?string $urlPath = null;
    public Component $component;
    private ?FileSystemRouter $router = null;
    private bool $dynamic = false;
    public function isDynamic(): bool
    {
        return $this->dynamic;
    }

    public bool $isLayout = false; // Layouts cannot be visited directly

    public function __construct(string $path, FileSystemRouter $router)
    {
        if ($path == '') {
            throw new \Exception('Route path cannot be empty');
        }

        $this->urlPath = Route::getURLPathFromPath($path);
        $this->router = $router;


        $componentPath = $router->componentsRootPath . '/' . $path;
        // Check if this is a layout
        if (substr($componentPath, -strlen($router->layoutFileName)) == $router->layoutFileName) {
            $this->isLayout = true;
        }
        
        // Remove php
        if (substr($componentPath, -4) == '.php') {
            $componentPath = substr($componentPath, 0, -4);
        }

        
        $this->component = new Component($componentPath, $this->router->framework);


        // Decide if this is dynamic route (contains [ and ] in one of the url parts)
        $urlParts = explode('/', $this->urlPath);
        foreach ($urlParts as $urlPart) {
            if (substr($urlPart, 0, 1) == '[' && substr($urlPart, -1) == ']') {
                $this->dynamic = true;
                break;
            }
        }
    }

    public function matches(string $url)
    {
        // If it's a layout, it cannot be visited directly
        if ($this->isLayout) {
            return false;
        }
        echo $this->urlPath . ' == ' . $url . '<br>';
        //return $this->urlPath == $url;

        // Split url path
        $routeURLParts = explode('/', $this->urlPath);
        $requestURLParts = explode('/', $url);

        if (count($routeURLParts) != count($requestURLParts)) {
            return false;
        }

        for ($i = 0; $i < count($routeURLParts); $i++) {
            $routeURLPart = $routeURLParts[$i];
            $requestURLPart = $requestURLParts[$i];

            //echo $routeURLPart . ' == ' . $requestURLPart . '<br>';

            if ($routeURLPart == $requestURLPart) {
                continue;
            } else if (substr($routeURLPart, 0, 1) == '[') {
                continue;
            } else {
                return false;
            }
        }

        return true;
    }

    private string $renderedUrl;
    private array $renderedQuery;

    public function render(string $url, $query = array(), string|array $content = null)
    {
        $this->renderedUrl = $url;
        $this->renderedQuery = $query;

        //echo "Rendering route: " . $this->urlPath . " for url: " . $url . "<br>";
        
        $queryFromURL = $this->extractDataFromURL($url);
        $query = array_merge($query, $queryFromURL);


        $props = [
            'query' => $query,
            'router' => $this->router,
            'route' => $this
        ];
 
        // For layouts
        if ($content != null) {
            $props['content'] = $content;
        }

        $this->component->setProps($props);

        $renderedComponent = $this->component->render();

        // Apply layouts (if this is not a layout)
        // Also don't apply if rewritten, because it's already applied in the rewritten route
        if (!$this->isLayout && !$this->rewritten) {
            $renderedComponent = $this->applyLayouts($renderedComponent);
        }

        return $renderedComponent;
    }

    private function extractDataFromUrl($url)
    {
        $data = array();

        $urlParts = explode('/', $url);
        $routeParts = explode('/', $this->urlPath);

        for ($i = 0; $i < count($routeParts); $i++) {
            $routePart = $routeParts[$i] ?? '';
            $urlPart = $urlParts[$i] ?? '';

            if (substr($routePart, 0, 1) == '[' && substr($routePart, -1) == ']') {
                // url decode
                $decodedURLPart = urldecode($urlPart);
                $data[substr($routePart, 1, -1)] = $decodedURLPart; // TODO: maybe use a separate array for this
            }
        }

        return $data;
    }

    public function getLayouts()
    {
        /**
         * @var Route[] $layouts
         */
        $layouts = array(); // list of layouts that apply to this route
        $layoutFilename = $this->router->layoutFileName;

        // Split url path
        // "/" is an exception
        $routeParts = explode('/', $this->urlPath);

        // If last is empty, remove it
        // (to avoid a case where "/" equals ['', ''] and that renders the root layout twice)
        if (count($routeParts) > 0 && $routeParts[count($routeParts) - 1] == '') {
            array_pop($routeParts);
        }

        //Utils::debug_print($routeParts);
        //Utils::debug_print($this->urlPath);

        for ($i = 0; $i < count($routeParts); $i++) {
            // Find layout at this level
            $layoutRelativePath = implode('/', array_slice($routeParts, 0, $i + 1)) . '/' . $layoutFilename;
            $layoutPath = $this->router->rootFilesystemPath . '/' . $layoutRelativePath;
            $layoutPath = Utils::fix_path($layoutPath);

            if (file_exists($layoutPath)) {
                $newLayout = new Route($layoutRelativePath, $this->router);
                $layouts[] = $newLayout;
            }
        }

        // If this is a layout, remove it from the list (otherwise an infinite loop would occur)
        if ($this->isLayout) {
            array_pop($layouts);
        }

        //Utils::debug_print($layouts);

        return $layouts;
    }

    public function applyLayouts(string|array $renderedComponent)
    {
        $layouts = $this->getLayouts();

        // Apply layouts (backwards)
        for ($i = count($layouts) - 1; $i >= 0; $i--) {
            $layout = $layouts[$i];
            $renderedComponent = $layout->render($this->renderedUrl, $this->renderedQuery, $renderedComponent);
        }

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
        return $newRoute->render($this->renderedUrl, $this->renderedQuery);
    }


    /* Default string conversion */
    public function __toString()
    {
        return "Error: Route to string conversion is not possible.";
    }



    public static function getURLPathFromPath(string $path): string
    {
        $urlPath = $path;

        // Remove .php extension
        if (substr($urlPath, -4) == '.php') {
            $urlPath = substr($urlPath, 0, -4);
        }

        // Remove index from the end
        if (substr($urlPath, -5) == 'index') {
            $urlPath = substr($urlPath, 0, -5);
        }

        // Remove trailing slash
        if (substr($urlPath, -1) == '/') {
            $urlPath = substr($urlPath, 0, -1);
        }

        // Add leading slash
        if (substr($urlPath, 0, 1) != '/') {
            $urlPath = '/' . $urlPath;
        }

        /* // if only /, then make it empty
        if ($urlPath == '/') {
            $urlPath = '';
        } */

        return $urlPath;
    }
}
