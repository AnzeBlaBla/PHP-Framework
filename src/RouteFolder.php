<?php

namespace AnzeBlaBla\Framework;

class RouteFolder extends RouteItem
{
    public ?string $dynamicRoute = null;
    public bool $hasLayout = false;
    /**
     * @var RouteItem[]
     */
    protected array $subItems = [];


    public function __construct(string $path, FileSystemRouter $router)
    {
        parent::__construct($path, $router);
    }


    public function addItem(string $name, RouteItem $item)
    {
        $this->subItems[$name] = $item;

        if ($item->isDynamic()) {
            if ($this->dynamicRoute) {
                throw new \Exception('Multiple dynamic routes for route ' . $this->path . ' (' . $this->dynamicRoute . ' and ' . $item->path . ')');
            }

            $this->dynamicRoute = $name;
        }
        // layout
        else if ($name == $this->router->layoutName) {
            $this->hasLayout = true;
        }
    }

    public function getItem(string $path): ?RouteItem
    {
        //Utils::debug_print("Getting item $path from route ", $this->subItems);
        if (isset($this->subItems[$path])) {
            return $this->subItems[$path];
        }

        return null;
    }

    public function getDynamicRoute(): ?RouteItem
    {
        if ($this->dynamicRoute) {
            return $this->subItems[$this->dynamicRoute];
        }

        return null;
    }

    public function getLayout(): ?RouteItem
    {
        if ($this->hasLayout) {
            return $this->subItems[$this->router->layoutName];
        }

        return null;
    }

    public function hasSubroutes(): bool
    {
        return count($this->subItems) > 0;
    }
}
