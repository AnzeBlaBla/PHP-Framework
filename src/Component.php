<?php

namespace AnzeBlaBla\Framework;

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

        // key must start with letter
        if ($key != null && !preg_match('/^[a-zA-Z]/', $key))
            throw new \Exception("Key must start with a letter");

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
        {
            // We add a 'c' to make sure it starts with a letter (otherwise css selectors break)
            $this->uniqueID = 'c' . md5(implode($this->componentTreePath)); 
        }

        self::$currentlyRendering = $this;
        self::$currentChildCount = 0;

        /* Call render function */
        try {

            ob_start();
            $componentReturn = require $this->fileSystemPath;
            $componentHTML = ob_get_clean();
        } catch (\Exception $e) {
            $componentHTML = "<div style='color: red;'>Error rendering component: {$e->getMessage()}</div>";
        }

        /* Set component state */
        $this->state = ComponentState::Rendered;

        /* Handle render tree related stuff */
        self::$currentlyRendering = $this->parentComponent;
        self::$currentChildCount = $this->indexInParent + 1;

        if ($componentHTML != '') {
            return <<<HTML
                    <!--$this->uniqueID-->
                    {$componentHTML}
                    <!--$this->uniqueID-->
                HTML;
        } else {
            return $componentReturn;
        }
    }

    /* Default string conversion */
    public function __toString()
    {
        return $this->render();
    }

# Helper functions

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
     * Helper to get unique ID
     * @return string
     */
    public function id()
    {
        return $this->uniqueID;
    }

#endregion


    /**
     * Debug function to get component tree location
     * @return string
     */
    public function _treeLocation()
    {
        return implode(' > ', $this->componentTreePath);
    }
}
