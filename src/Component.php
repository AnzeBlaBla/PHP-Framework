<?php

namespace AnzeBlaBla\Framework;

use ReflectionFunction;

enum ComponentState
{
    case Uninitialized;
    case Initialized;
    case Rendering;
    case Rendered;
    case Updated;
}

class Component
{
    private $componentName;
    private $renderFunction;
    private $props;
    private $data = null;
    private $helpers;

    public ComponentState $state = ComponentState::Uninitialized;

    static ?Component $lastConstructed = null;
    static ?Component $lastRendered = null;

    private $parentComponent = null;
    private $componentTreePath = [];
    public $uniqueID = null;

    public function markUpdated()
    {
        $this->state = ComponentState::Updated;
    }

    /**
     * @param Closure|string $renderFunction
     * @param Helpers $helpers
     * @param array $props
     */
    public function __construct($renderFunction, $helpers = null, $props = [], $key = null)
    {
        /* Set last constructed component */
        self::$lastConstructed = $this;

        $this->uniqueID = $key;
        if ($helpers == null)
            $helpers = new Helpers();
        $this->helpers = $helpers;

        /* Get component name (file name) using reflection */
        $reflection = new ReflectionFunction($renderFunction);

        $fileName = $reflection->getFileName();
        $fileName = str_replace('.php', '', $fileName);
        $fileName = str_replace($helpers->framework->componentsRoot, '', $fileName);
        $this->componentName = $fileName;

        $this->renderFunction = $renderFunction;

        /* Set component props */
        $this->props = new Properties($props);

        /* Set component state */
        $this->state = ComponentState::Initialized;
    }

    public function render()
    {
        /* Set component state */
        $this->state = ComponentState::Rendering;

        /* Handle render tree related stuff */
        $this->parentComponent = self::$lastRendered;
        if ($this->parentComponent == null) {
            $this->componentTreePath = [$this->componentName];
        } else {
            $this->componentTreePath = array_merge($this->parentComponent->componentTreePath, [$this->componentName]);
        }
        if ($this->uniqueID == null)
            $this->uniqueID = md5(implode($this->componentTreePath));

        self::$lastRendered = $this;

        /* Set component data (now that we know our ID) */
        $this->data = new ComponentData($this->helpers->sessionState, $this);

        /* Call render function */
        try {
            $componentHTML = $this->renderFunction->call($this, $this->helpers);
        } catch (\Exception $e) {
            $componentHTML = "<div style='color: red;'>Error rendering component: {$e->getMessage()}</div>";
        }

        /* Set component state */
        $this->state = ComponentState::Rendered;


        Framework::renderFrontendDependencies(); // In case they weren't rendered yet

        if (Framework::$renderMode == RenderMode::Raw) {
            return <<<HTML
                <!--$this->uniqueID-->
                {$componentHTML}
                <!--$this->uniqueID-->
            HTML;
        } else if (Framework::$renderMode == RenderMode::WebComponent) {
            return <<<HTML
            
                <template id="template-{$this->uniqueID}">
                    <!--$this->uniqueID-->
                    {$componentHTML}
                    <!--$this->uniqueID-->
                </template>

                <framework-component
                    uniqueid="{$this->uniqueID}"
                    component="{$this->componentName}"
                ></framework-component>
            HTML;
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

    /* Debugging functions */
    public function treeLocation()
    {
        return implode(' > ', $this->componentTreePath);
    }
}
