<?php

namespace AnzeBlaBla\Framework;

/* Class that stores data in JSON in session */

class SessionState
{
    private array $state = [];
    private string $key;

    public function __construct($key = 'DEFAULT')
    {
        // if session not started, start it
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        $this->key = $key;

        if (isset($_SESSION[$key])) {
            $this->state = json_decode($_SESSION[$key], true);
        }

        register_shutdown_function([$this, 'save']);

        //print_r($this->state);

        return $this;
    }

    public function &__get($name)
    {
        //echo "Getting $name\n";
        if (isset($this->state[$name])) {
            //return $this->state[$name];

            // Return reference
            return $this->state[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        //echo "Setting $name to $value\n";
        $this->state[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->state[$name]);
    }

    public function __unset($name)
    {
        unset($this->state[$name]);
    }

    public function __toString()
    {
        return json_encode($this->state);
    }

    public function save()
    {
        $_SESSION[$this->key] = json_encode($this->state);
    }
}
