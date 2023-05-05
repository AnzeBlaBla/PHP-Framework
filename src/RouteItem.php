<?php

namespace AnzeBlaBla\Framework;

/**
 * Either a route or a folder of routes.
 */
class RouteItem
{
    /**
     * @var RouteItem[]
     */
    private array $subItems = [];
    public ?RouteItem $dynamicRoute = null;
    public string $path;

    private ?FileSystemRouter $router = null;

    private bool $dynamic = false;
    public function isDynamic(): bool
    {
        return $this->dynamic;
    }

    private ?string $urlPath = null;

    public function __construct(string $path, FileSystemRouter $router)
    {
        $this->path = $path;
        $this->router = $router;

        $this->urlPath = self::getURLPathFromPath($path);

        print $this->path . " to url " . $this->urlPath . "<br>";

        // Decide if this is dynamic route (contains [ and ] in the last part of the path)
         $urlParts = explode('/', $this->urlPath);

        if (count($urlParts) > 0) {
            $lastURLPart = $urlParts[count($urlParts) - 1];
            if (substr($lastURLPart, 0, 1) == '[' && substr($lastURLPart, -1) == ']') {
                $this->dynamic = true;
            }
        }
        /* foreach ($urlParts as $urlPart) {
            if (substr($urlPart, 0, 1) == '[' && substr($urlPart, -1) == ']') {
                $this->dynamic = true;
                break;
            }
        } */
    }

    public function addItem(string $name, RouteItem $item)
    {
        $this->subItems[$name] = $item;

        if ($item->isDynamic()) {
            if ($this->dynamicRoute) {
                throw new \Exception('Multiple dynamic routes for route ' . $this->path . ' (' . $this->dynamicRoute->path . ' and ' . $item->path . ')');
            }

            $this->dynamicRoute = $item;
        }
    }

    public function getItem(string $path): ?RouteItem
    {
        if (isset($this->subItems[$path])) {
            return $this->subItems[$path];
        }

        return null;
    }

    public function hasSubroutes(): bool
    {
        return count($this->subItems) > 0;
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
