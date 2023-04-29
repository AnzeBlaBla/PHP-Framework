<?php

namespace AnzeBlaBla\Framework;

use FilesystemIterator;

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

    public function __construct(string $path, FileSystemRouter $router)
    {
        if($path == '') {
            throw new \Exception('Route path cannot be empty');
        }

        $this->urlPath = Route::getURLPathFromPath($path);
        $this->router = $router;


        $componentPath = Utils::fix_path($router->rootFilesystemPath . '/' . $path);
        $this->component = new Component(require($componentPath), $this->router->framework->getHelpers());


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
        //echo $this->urlPath . ' == ' . $url . '<br>';
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

    public function render(string $url, $query = array())
    {
        $this->renderedUrl = $url;
        $this->renderedQuery = $query;

        //echo "Rendering route: " . $this->urlPath . " for url: " . $url . "<br>";
        // Extract data from url if needed
        $urlParts = explode('/', $url);
        $routeParts = explode('/', $this->urlPath);

        for ($i = 0; $i < count($routeParts); $i++) {
            $routePart = $routeParts[$i];
            $urlPart = $urlParts[$i];

            if (substr($routePart, 0, 1) == '[' && substr($routePart, -1) == ']') {
                // url decode
                $decodedURLPart = urldecode($urlPart);
                $query[substr($routePart, 1, -1)] = $decodedURLPart; // TODO: maybe use a separate array for this
            }
        }

        //Utils::debug_print($layouts);

        $this->component->setProps([
            'query' => $query,
            'router' => $this->router,
            'route' => $this
        ]);

        $renderedComponent = $this->component->render();

        // Only apply layouts if this route was not rewritten (otherwise it would be applied twice)
        if (!$this->rewritten) {
            $renderedComponent = $this->applyLayouts($renderedComponent);
        }

        return $renderedComponent;
    }

    public function getLayouts()
    {
        $layouts = array(); // list of layouts that apply to this route
        $layoutFilename = $this->router->getLayoutFilename();

        $routeParts = explode('/', $this->urlPath);
        for ($i = 0; $i < count($routeParts); $i++) {
            // Find layout at this level
            $layoutPath = $this->router->rootFilesystemPath . '/' . implode('/', array_slice($routeParts, 0, $i + 1)) . '/' . $layoutFilename;
            $layoutPath = Utils::fix_path($layoutPath);
            if (file_exists($layoutPath)) {
                $layouts[] = new Component(require($layoutPath), $this->router->framework->getHelpers());
            }
        }

        return $layouts;
    }

    public function applyLayouts(string|array $renderedComponent)
    {
        $layouts = $this->getLayouts();

        // Apply layouts (backwards)
        for ($i = count($layouts) - 1; $i >= 0; $i--) {
            $layout = $layouts[$i];
            $layout->setProps(['content' => $renderedComponent]);
            $renderedComponent = $layout->render();
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

        return $urlPath;
    }
}
