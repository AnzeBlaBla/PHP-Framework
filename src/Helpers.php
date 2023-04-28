<?php

namespace AnzeBlaBla\Framework;

class Helpers
{
    /* Instance for use when no framework is initialized */
    private static Helpers $instance;
    public static function getInstance(): Helpers
    {
        if (!isset(self::$instance)) {
            self::$instance = Framework::getInstance()->getHelpers();
        }
        return self::$instance;
    }

    private $specialFunctions = [];

    // Is set when a function was called
    // This one will actually be called when rendered
    private $functionToBeCalled = null;
    private $functionArgs = [];
    public function __callSpecialFunction($functionID, $args)
    {
        $this->functionToBeCalled = $functionID;
        $this->functionArgs = $args;
    }

    public SessionState $sessionState;
    public ?DBConnection $db;
    public ?Framework $framework;

    /**
     * @param SessionState $sessionState
     * @param DBConnection $dbConnection
     * @param Framework $framework
     */
    public function __construct($sessionState = null, $dbConnection = null, $framework = null)
    {
        if($sessionState == null)
            $sessionState = new SessionState();
        $this->sessionState = $sessionState;
        $this->db = $dbConnection;
        $this->framework = $framework;
    }

    public function component($componentPath, $props = [], $key = null)
    {
        $compPath = ($this->framework->componentsRoot ?? '') . $componentPath . '.php';
        return new Component(require($compPath), $this, $props, $key);
    }

    public function function($function)
    {
        $ownerUniqueID = Component::$lastRendered ? Component::$lastRendered->uniqueID : 'root';

        $newFuncID = count($this->specialFunctions) + 1 . '_' . $ownerUniqueID;
        $newFunc = new SpecialFunction($function, $newFuncID);

        $this->specialFunctions[$newFuncID] = $newFunc;

        if ($this->functionToBeCalled == $newFuncID) {
            $this->functionToBeCalled = null;
            $args = $this->functionArgs;
            $this->functionArgs = [];

            // Mark component as updated
            Component::$lastRendered->markUpdated();
            
            $newFunc->call(...$args);

            
        }

        return $newFunc;
    }

    public function fileSystemRouter($path)
    {
        return new FileSystemRouter($path, $this->framework);
    }

    public function setUID($uid)
    {
        Component::$lastRendered->uniqueID = $uid;
    }

    public static function if($condition, $ifTrue, $ifFalse = '')
    {
        if (is_callable($condition))
        {
            $condition = $condition();
        }
        if ($condition)
        {
            if (is_callable($ifTrue))
            {
                $ifTrue = $ifTrue();
            }
            return $ifTrue;
        } else {
            if (is_callable($ifFalse))
            {
                $ifFalse = $ifFalse();
            }
            return $ifFalse;
        }
        
    }

    // Function to map array of data to html
    public static function map($array, $function)
    {
        $result = '';
        foreach ($array as $key => $value) {
            $result .= $function($value, $key);
        }
        return $result;
    }

    public static function onsubmit($function)
    {
        return "event.preventDefault(); {$function}(getFormData(event.target));";
    }

    public static function status($status)
    {
        http_response_code($status);
    }

    public static function redirect($url)
    {
        echo "<script>window.location.href = '$url'; console.log('redir to $url')</script>";
    }

    public static function reload()
    {
        return Helpers::redirect($_SERVER['REQUEST_URI']);
    }
}