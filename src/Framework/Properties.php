<?php

namespace AnzeBlaBla\Framework;

class Properties {
    private $props;

    public function __construct($props = [])
    {
        foreach ($props as $key => $value) {
            $this->props[$key] = $value;
        }
    }

    public function __get($name)
    {
        if (isset($this->props[$name])) {
            return $this->props[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        $this->props[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->props[$name]);
    }

    public function __unset($name)
    {
        unset($this->props[$name]);
    }

    public function __toString()
    {
        return json_encode($this->props);
    }
}