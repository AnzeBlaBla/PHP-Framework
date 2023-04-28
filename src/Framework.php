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
    private Helpers $helpers;
    public function getHelpers()
    {
        return $this->helpers;
    }
    private SessionState $sessionState;
    private ?DBConnection $dbConnection;
    public $componentsRoot;

    public function __construct(Closure $renderFunction = null, DBConnection $dbConnection = null, Helpers $helpers = null)
    {
        $this->sessionState = new SessionState('Framework');

        $backtrace = debug_backtrace();
        $this->componentsRoot = dirname($backtrace[0]['file']);
        if ($helpers == null)
            $this->helpers = new Helpers($this->sessionState, $dbConnection, $this);
        else
            $this->helpers = $helpers;

        $this->dbConnection = $dbConnection;


        $this->handleRequestData();

        if ($renderFunction != null)
            $this->rootComponent = new Component($renderFunction, $this->helpers);
    }

    /**
     * Sets the root folder from where the framework will search for components.
     * @param string $root
     */
    public function setComponentRoot($root)
    {
        $root = realpath($root);
        if (substr($root, -1) != '/')
            $root .= '/';
        $this->componentsRoot = $root;
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
                    $this->helpers->__callSpecialFunction($requestData['specialFunctionID'], $requestData['args']);
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
