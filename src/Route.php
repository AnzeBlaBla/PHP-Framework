<?php

namespace AnzeBlaBla\Framework;

use FilesystemIterator;

class Route
{
    private ?string $urlPath = null;
    private ?string $fileSystemPath = null;
    private ?FileSystemRouter $router = null;
    private bool $dynamic = false;
    public function isDynamic(): bool
    {
        return $this->dynamic;
    }

    public function __construct(string $path, FileSystemRouter $router)
    {
        $this->fileSystemPath = $router->rootFilesystemPath . '/' . $path;
        $this->urlPath = Route::getURLPathFromPath($path);
        $this->router = $router;

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

    public function render(string $url, $query = array())
    {
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

        $component = new Component(require($this->fileSystemPath), $this->router->framework->getHelpers(), [
            'query' => $query,
            'router' => $this->router,
            'route' => $this
        ]);

        //print_r($component);

        return $component->render();
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
        if (substr($urlPath, -4) == '.php')
        {
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
