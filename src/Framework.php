<?php

namespace AnzeBlaBla\Framework;

class Framework
{
    public static string $HEAD_PORTAL = 'head';
    public static string $FRAMEWORK_REQUEST_IDENTIFIER = 'framework_request_g8j484jsf';

    private Component $rootComponent;
    public $componentsRoot;

    public function __construct(string $componentsRoot = null, $rootComponent = null)
    {

        if ($componentsRoot) {
            $this->setComponentsRoot($componentsRoot);
        } else {
            $backtrace = debug_backtrace();
            $this->setComponentsRoot(dirname($backtrace[0]['file']));
        }

        if ($rootComponent) {
            $this->setRootComponent($rootComponent);
        }
    }

    public ?RequestData $requestData = null;

    /**
     * Handles the request data and calls the appropriate functions. (die()s on internal requests)
     */
    private function handleRequestData()
    {
        $this->requestData = new RequestData();

        // Handle built-in framework-related requests
        if (isset($this->requestData->json) && $this->requestData->json[self::$FRAMEWORK_REQUEST_IDENTIFIER] ?? null)
        {
            $action = $this->requestData->json['action'] ?? null;

            switch ($action) {
                case 'getComponent':
                    $component = new Component($this->requestData['componentPath'], $this, $this->requestData['props'] ?? [], $this->requestData['key'] ?? null);
                    $renderedComponent = $component->render();
                    if (is_string($renderedComponent)) {
                        echo $renderedComponent;
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode($renderedComponent);
                    }
                    
                    die;

                    break;
                default:
                    break;
            }
        }
    }
    /**
     * Renders the root component.
     */
    public function render()
    {
        // Handle internal requests - dies on handle, so execution continues only if there are no internal requests
        $this->handleRequestData();

        $renderBuffer = "";

        if ($this->rootComponent != null)
            $renderBuffer .= $this->rootComponent->render();
        else
            throw new \Exception('Trying to render Framework while root component is not set.');    


        // Inject framework HTML (JS, CSS, etc.)
        $depsHTML = $this->getDependenciesHTML();
        // if there was no head portal, just print the dependencies
        if (!isset($this->portals[self::$HEAD_PORTAL]))
        {
            $renderBuffer = $depsHTML . $renderBuffer;
        }
        else
        {
            $this->getPortal(self::$HEAD_PORTAL)->append($depsHTML);
        }

        // Replace the portals
        foreach ($this->portals as $portal) {
            $renderBuffer = str_replace($portal->getPlaceholder(), $portal->getContent(), $renderBuffer);
        }

        echo $renderBuffer;
    }

    /**
     * Gets the dependencies HTML.
     * @return string
     */
    public static function getDependenciesHTML()
    {
        //include_once(__DIR__ . '/frontend.php');
        return file_get_contents(__DIR__ . '/frontend.html');
    }

#region Portals

    /**
     * @var \AnzeBlaBla\Framework\Portal[] $portals
     */
    private array $portals = [];

    /**
     * Creates the head portal.
     * @return \AnzeBlaBla\Framework\Portal
     */
    public function createHeadPortal()
    {
        return $this->createPortal(self::$HEAD_PORTAL);
    }

    /**
     * Creates a portal.
     * @param string $portalKey
     * @return \AnzeBlaBla\Framework\Portal
     */
    public function createPortal(string $portalKey, mixed $defaultContent = null)
    {

        if (isset($this->portals[$portalKey])) {
            //throw new \Exception('Portal with key "' . $portalKey . '" already exists.');
            $portal = $this->portals[$portalKey];
        } else {
            $portal = new Portal($portalKey);
        }
        if ($defaultContent !== null)
            $portal->setDefaultContent($defaultContent);
        $this->portals[$portalKey] = $portal;
        return $portal;
    }

    /**
     * Gets a portal.
     * @param string $portalKey
     * @return \AnzeBlaBla\Framework\Portal
     */
    public function getPortal(string $portalKey)
    {
        if (!isset($this->portals[$portalKey])) {
            // throw new \Exception('Portal with key "' . $portalKey . '" does not exist.');
            return $this->createPortal($portalKey);
        }

        return $this->portals[$portalKey];
    }


#endregion

#region Helpers

    /**
     * Creates a router
     * @param string $path
     */
    public function fileSystemRouter($path)
    {
        return new FileSystemRouter($path, $this);
    }

#endregion

#region Getters/Setters

    /**
     * Sets the root folder from where the framework will search for components.
     * @param string $root
     * @return \AnzeBlaBla\Framework\Framework
     */
    public function setComponentsRoot($root)
    {
        $root = realpath($root);
        if (substr($root, -1) != '/')
            $root .= '/';
        $this->componentsRoot = $root;

        return $this;
    }

    /**
     * Sets the root component.
     * @param string $componentPath
     * @return \AnzeBlaBla\Framework\Framework
     */
    public function setRootComponent(string $componentPath)
    {
        $this->rootComponent = new Component($componentPath, $this);

        return $this;
    }

#endregion
}
