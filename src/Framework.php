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


        $this->handleRequestData();
    }

    /**
     * Sets the root folder from where the framework will search for components.
     * @param string $root
     */
    public function setComponentsRoot($root)
    {
        $root = realpath($root);
        if (substr($root, -1) != '/')
            $root .= '/';
        $this->componentsRoot = $root;
    }

    /**
     * Sets the root component.
     * @param string $componentPath
     */
    public function setRootComponent(string $componentPath)
    {
        $this->rootComponent = new Component($componentPath, $this);
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
            }
        }
    }

    /**
     * Renders the root component.
     */
    public function render()
    {
        echo $this->renderDependenciesHTML();

        if ($this->rootComponent != null)
            echo $this->rootComponent->render();
        else
            throw new \Exception('Trying to render Framework while root component is not set.');
    }

    /**
     * Renders the dependencies HTML.
     * @return string
     */
    public static function renderDependenciesHTML()
    {
        include_once(__DIR__ . '/frontend.php');
    }
}
