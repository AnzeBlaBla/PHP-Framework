<?php

namespace AnzeBlaBla\Framework;

use ReflectionFunction;
use Closure;

enum ComponentState
{
    case Uninitialized;
    case Initialized;
    case Rendering;
    case Rendered;
    //case Updated;
}

class Component
{
    // TODO: maybe move these 2 to framework, instead of being static
    static ?Component $lastConstructed = null; // Last component on which constructor was called
    static ?Component $currentlyRendering = null; // Last component that was rendered (really the one being currently rendered)
    static int $currentChildCount = 0; // Current count of children being rendered (used for key generation)

    private string $componentName;
    private string $fileSystemPath;
    private Properties $props;
    private ?ComponentData $data = null;
    private Framework $framework;

    public ComponentState $state = ComponentState::Uninitialized;

    private ?Component $parentComponent = null;
    private int $indexInParent = 0;
    private $componentTreePath = [];
    public ?string $uniqueID = null;


    /**
     * Component constructor.
     * @param string $componentPath
     * @param Framework $framework
     * @param array $props
     * @param string|null $key
     */
    public function __construct($componentPath, $framework = null, $props = [], $key = null)
    {
        //echo "Constructing component $componentPath<br>";

        /* Set last constructed component */
        self::$lastConstructed = $this;

        $this->uniqueID = $key;

        /* Set framework to default instance if not set */
        if ($framework == null)
            $framework = Framework::getInstance();
        $this->framework = $framework;

        /* Set component name */
        $this->componentName = $componentPath;

        //echo("Name: " . $this->componentName . " - Root: " . $this->framework->componentsRoot . "<br>");

        $this->fileSystemPath = Utils::fix_path(($this->framework->componentsRoot ?? '') . $componentPath . '.php');

        //echo "Final: " . $this->fileSystemPath . "<br>";
        /* Set component props */
        $this->props = new Properties($props);

        /* Set component state */
        $this->state = ComponentState::Initialized;
    }

    public function setProps($props)
    {
        // We can only set props if component is not rendered yet
        if ($this->state == ComponentState::Rendered || $this->state == ComponentState::Rendering)
            throw new \Exception("Cannot set props on rendered component");
        $this->props = new Properties($props);
    }

    /**
     * Ensures props are provided (and optionally, their types)
     * @param array $props
     * @throws \Exception
     */
    public function ensureProps(array $props)
    {
        $this->props->ensure($props);
    }

    /**
     * Render component
     * @return string|array
     */
    public function render()
    {
        // Must be in initialized state
        if ($this->state != ComponentState::Initialized)
            throw new \Exception("Component must be in initialized state to render");

        /* Set component state */
        $this->state = ComponentState::Rendering;

        /* Handle render tree related stuff */
        $this->parentComponent = self::$currentlyRendering;
        if ($this->parentComponent != null)
            $this->indexInParent = self::$currentChildCount;

        if ($this->parentComponent == null) {
            $this->componentTreePath = [$this->componentName . "-" . $this->indexInParent];
        } else {
            $this->componentTreePath = array_merge($this->parentComponent->componentTreePath, [$this->componentName . "-" . $this->indexInParent]);
        }
        if ($this->uniqueID == null)
            $this->uniqueID = md5(implode($this->componentTreePath));

        self::$currentlyRendering = $this;
        self::$currentChildCount = 0;

        /* Set component data (now that we know our ID) */
        $this->data = new ComponentData($this->framework->sessionState, $this);

        /* Call render function */
        try {

            // Anon function rendering commented for optimization reasons, uncomment if needed
            /* $renderFunc = function()
            { */

            ob_start();
            $componentReturn = require($this->fileSystemPath);
            $renderedComponent = ob_get_clean();

            /*       return [
                    'componentReturn' => $componentReturn,
                    'renderedComponent' => $renderedComponent
                ];
            };

            $renderFunc = Closure::bind($renderFunc, $this);
            $renderRes = $renderFunc->call($this);

            $componentReturn = $renderRes['componentReturn'];
            $renderedComponent = $renderRes['renderedComponent']; */

            //print_r($componentReturn);
        } catch (\Exception $e) {
            $renderedComponent = "<div style='color: red;'>Error rendering component: {$e->getMessage()}</div>";
        }

        /* Set component state */
        $this->state = ComponentState::Rendered;

        /* Handle render tree related stuff */
        self::$currentlyRendering = $this->parentComponent;
        self::$currentChildCount = $this->indexInParent + 1;

        // If renderedComponent printed any data, consider it HTML
        if ($renderedComponent != '') {
            //echo "Component is string<br>";
            //Utils::debug_print($renderedComponent);
            if (Framework::$renderMode == RenderMode::Raw) {
                return <<<HTML
                    <!--$this->uniqueID-->
                    {$renderedComponent}
                    <!--$this->uniqueID-->
                HTML;
            } else if (Framework::$renderMode == RenderMode::WebComponent) {
                return <<<HTML
                
                    <template id="template-{$this->uniqueID}">
                        <!--$this->uniqueID-->
                        {$renderedComponent}
                        <!--$this->uniqueID-->
                    </template>
    
                    <framework-component
                        uniqueid="{$this->uniqueID}"
                        component="{$this->componentName}"
                    ></framework-component>
                HTML;
            }
            // If a component returns an object, it's data
        } else if (is_object($componentReturn) || is_array($componentReturn)) {
            //echo "Component is array<br>";
            // If renderedComponent is array, return it (used for API components that return JSON)
            return $componentReturn;
        } else {
            // If renderedComponent is neither string nor array, it's an error
            //Utils::debug_print($renderedComponent);
            //Utils::debug_print($componentReturn);
            return "<div style='color: red;'>Error rendering component: Invalid return type</div>";
        }
    }

    /* Default string conversion */
    public function __toString()
    {
        return $this->render();
    }

    /* Getters and setters */
    public function __get($name)
    {
        //echo "getting $name";
        //print_r($this->data);
        if ($this->data == null) {
            throw new \Exception("Trying to get property of component before it was rendered (in component {$this->componentName})");
        }
        return $this->data->{$name};
    }

    public function __set($name, $value)
    {
        //echo "setting $name";
        //print_r($this->data);
        if ($this->data == null) {
            throw new \Exception("Trying to set property of component before it was rendered (in component {$this->componentName})");
        }
        $this->data->{$name} = $value;
    }

    /* Helper functions */

    /**
     * Create another component
     * @param string $componentPath
     * @param array $props
     * @param string|null $key
     * @return Component
     */
    public function component($componentPath, $props = [], $key = null)
    {
        return new Component($componentPath, $this->framework, $props, $key);
    }

    /**
     * Helper to create a file system router
     * @param string $path
     * @return FileSystemRouter
     */
    public function fileSystemRouter($path)
    {
        return $this->framework->fileSystemRouter($path);
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
     * Debug function to get component tree location
     * @return string
     */
    public function _treeLocation()
    {
        return implode(' > ', $this->componentTreePath);
    }
}
