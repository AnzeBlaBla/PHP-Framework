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

    /**
     * Ensures props are provided (and optionally, their types)
     * @param array $props
     * @throws \Exception
     */
    public function ensure($propsToEnsure)
    {
        foreach ($propsToEnsure as $key => $value) {
            // If key is string, check if prop is set and if it's type is correct
            // Otherwise, check if prop is set
            if (is_string($key)) {
                if (!isset($this->props[$key]))
                    throw new \Exception("Prop '$key' is not set");
                $propType = gettype($this->props[$key]);
                // If value is array, check if prop is of one of the types in array
                // Otherwise, check if prop is of the type
                if (is_array($value)) {
                    if (!in_array($propType, $value))
                    {
                        throw new \Exception("Prop '$key' is not of one of the types: [" . implode(', ', $value) . "] (is " . $propType . ")");
                    }
                } else if ($propType != $value)
                {
                    throw new \Exception("Prop '$key' is not of type '$value' (is " . $propType . ")");
                }
            } else {
                if (!isset($this->props[$value]))
                    throw new \Exception("Prop '$value' is not set");
            }
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