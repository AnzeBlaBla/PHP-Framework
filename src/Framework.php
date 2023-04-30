<?php

namespace AnzeBlaBla\Framework;

use Closure;

class Framework
{
    private static Framework $instance;
    public static function getInstance(): Framework
    {
        if (!isset(self::$instance))
            self::$instance = new Framework();
        return self::$instance;
    }

    public static RenderMode $renderMode = RenderMode::Raw;

    private Component $rootComponent;
    public SessionState $sessionState;
    private ?DBConnection $dbConnection;
    public $componentsRoot;

    public function __construct(DBConnection $dbConnection = null)
    {
        // If instance is not set, set it to this (so that first instance is the instance)
        if (!isset(self::$instance))
            self::$instance = $this;

        $this->sessionState = new SessionState('Framework');

        $backtrace = debug_backtrace();

        $this->componentsRoot = dirname($backtrace[0]['file']);

        $this->dbConnection = $dbConnection;
    }

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

    /**
     * Handles the request data and calls the appropriate functions.
     */
    private function handleRequestData()
    {
        // Data is either raw JSON or post data where the data field is the json
        $json = $_POST['data'] ?? file_get_contents('php://input');
        $requestData = json_decode($json, true);

        if (isset($requestData['action'])) {
            $action = $requestData['action'];

            switch ($action) {
                case 'callSpecialFunction':
                    //$this->helpers->__callSpecialFunction($requestData['specialFunctionID'], $requestData['args']);
                    throw new \Exception('Special functions are not implemented yet.');
                    break;
                case 'getComponent':
                    //Utils::debug_print('Getting component', $requestData);
                    $component = new Component($requestData['componentPath'], $this, $requestData['props'] ?? [], $requestData['key'] ?? null);
                    $renderedComponent = $component->render();
                    if (is_string($renderedComponent)) {
                        echo $renderedComponent;
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode($renderedComponent);
                    }
                    die;
                    break;
            }
        }
    }

    /**
     * Renders the root component.
     */
    public function render()
    {
        $this->handleRequestData();

        $renderBuffer = "";

        $renderBuffer .= $this->getDependenciesHTML();

        if ($this->rootComponent != null)
            $renderBuffer .= $this->rootComponent->render();
        else
            throw new \Exception('Trying to render Framework while root component is not set.');

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



    /* Helpers */

    /**
     * Creates a router
     * @param string $path
     */
    public function fileSystemRouter($path)
    {
        return new FileSystemRouter($path, $this);
    }
}
