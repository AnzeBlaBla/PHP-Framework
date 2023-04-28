<?php

namespace AnzeBlaBla\Framework;

use Closure;

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

    /**
     * @var SpecialFunction[] $specialFunctions
     */
    private $specialFunctions = [];

    // Is set when a function was called
    // This one will actually be called when rendered
    private ?string $functionToBeCalled = null;
    private array $functionArgs = [];
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
        if ($sessionState == null)
            $sessionState = new SessionState();
        $this->sessionState = $sessionState;
        $this->db = $dbConnection;
        $this->framework = $framework;
    }

    /**
     * Helper to create a component
     * @param string $componentPath
     * @param array $props
     * @param string|null $key
     * @return Component
     */
    public function component($componentPath, $props = [], $key = null)
    {
        $compPath = Utils::fix_path(($this->framework->componentsRoot ?? '') . $componentPath . '.php');
        return new Component(require($compPath), $this, $props, $key);
    }

    /**
     * Helper to create a function that can be called from javascript
     * @param Closure $function
     * @return SpecialFunction
     */
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

    /**
     * Helper to create a file system router
     * @param string $path
     * @return FileSystemRouter
     */
    public function fileSystemRouter($path)
    {
        return new FileSystemRouter($path, $this->framework);
    }


    /**
     * Helper for if statements
     * @param bool|callable $condition
     * @param string|callable $ifTrue
     * @param string|callable $ifFalse
     * @return mixed
     */
    public static function if($condition, $ifTrue, $ifFalse = '')
    {
        if (is_callable($condition)) {
            $condition = $condition();
        }
        if ($condition) {
            if (is_callable($ifTrue)) {
                $ifTrue = $ifTrue();
            }
            return $ifTrue;
        } else {
            if (is_callable($ifFalse)) {
                $ifFalse = $ifFalse();
            }
            return $ifFalse;
        }
    }

    /**
     * Helper for loops (maps array to string)
     * @param array $array
     * @param callable $function
     * @return string
     */
    public static function map($array, $function)
    {
        $result = '';
        foreach ($array as $key => $value) {
            $result .= $function($value, $key);
        }
        return $result;
    }

    /**
     * Helper for making an onsubmit function for forms
     * @param SpecialFunction $function
     * @return string
     */
    public static function onsubmit(SpecialFunction $function)
    {
        return "event.preventDefault(); {$function}(getFormData(event.target));";
    }

    /**
     * Helper to set response code
     * @param int $status
     * @return void
     */
    public static function status(int $status)
    {
        http_response_code($status);
    }

    /**
     * Helper for redirection (returns a script that redirects)
     * @param string $url
     * @return string
     */
    public static function redirect(string $url, bool $includeScriptTag = true)
    {
        if ($includeScriptTag)
            echo "<script>";
        echo "window.location.href = '$url'; console.log('redir to $url')";
        if ($includeScriptTag)
            echo "</script>";
    }

    /**
     * Helper for reloading the page (see redirect)
     * @return string
     */
    public static function reload()
    {
        return Helpers::redirect($_SERVER['REQUEST_URI']);
    }
}
