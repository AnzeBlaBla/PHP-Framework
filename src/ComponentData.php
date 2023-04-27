<?php

namespace AnzeBlaBla\Framework;

class ComponentData
{
    private $sessionState;
    private $component;
    private $uniqueID;

    public function __construct($sessionState, $component)
    {
        $this->sessionState = $sessionState;
        $this->component = $component;
        $this->uniqueID = $this->component->uniqueID;
    }

    private function initState()
    {
        if (!isset($this->sessionState->{$this->uniqueID})) {
            $this->sessionState->{$this->uniqueID} = [];
        }
    }

    public function __get($name)
    {
        $this->initState();

        /* echo "<br /><br />";
        echo "Getting $name<br />";
        print_r($this->sessionState->{$this->uniqueID});
        echo "<br />Component state:";
        print_r($this->component->state);
        echo "<br />isset:";
        print_r(isset($this->sessionState->{$this->uniqueID}[$name]));
        echo "<br /><br />"; */

        if (!isset($this->sessionState->{$this->uniqueID}[$name])) {
            return null;
        }
        return $this->sessionState->{$this->uniqueID}[$name];
    }

    public function __set($name, $value)
    {
        $this->initState();

        /* echo "<br /><br />";
        echo "Updating $name to $value<br />";
        print_r($this->sessionState->{$this->uniqueID});
        echo "<br />Component state:";
        print_r($this->component->state);
        echo "<br />isset:";
        print_r(isset($this->sessionState->{$this->uniqueID}[$name]));
        echo "<br /><br />"; */
        // If trying to set during render (not when actually called by functions), only set if not already set
        if ($this->component->state == ComponentState::Rendering) {
            if (isset($this->sessionState->{$this->uniqueID}[$name])) {
                //echo "Not setting";
                return;
            }
        }
        $this->sessionState->{$this->uniqueID}[$name] = $value;
    }

    public function __isset($name)
    {
        $this->initState();

        return isset($this->sessionState->{$this->uniqueID}[$name]);
    }

    public function __unset($name)
    {
        $this->initState();

        unset($this->sessionState->{$this->uniqueID}[$name]);
    }

    public function __toString()
    {
        $this->initState();

        return json_encode($this->sessionState->{$this->uniqueID});
    }
}