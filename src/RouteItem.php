<?php

namespace AnzeBlaBla\Framework;

/**
 * Either a route or a folder of routes.
 */
class RouteItem
{
    public string $path;

    protected ?FileSystemRouter $router = null;

    protected bool $dynamic = false;
    public function isDynamic(): bool
    {
        return $this->dynamic;
    }

    protected ?string $urlPath = null;

    public function __construct(string $path, FileSystemRouter $router)
    {
        //echo "RouteItem construct: $path<br>";
        $this->path = $path;
        $this->router = $router;

        $this->urlPath = self::getURLPathFromPath($path);

        //print $this->path . " to url " . $this->urlPath . "<br>";

        // Decide if this is dynamic route (contains [ and ] in the last part of the path)
        $urlParts = explode('/', $this->path);

        if (count($urlParts) > 0) {
            $lastURLPart = $urlParts[count($urlParts) - 1];
            if (substr($lastURLPart, 0, 1) == '[' && substr($lastURLPart, -1) == ']') {
                //Utils::debug_print("Dynamic route: ", $this->path);
                $this->dynamic = true;
            }
        }
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
