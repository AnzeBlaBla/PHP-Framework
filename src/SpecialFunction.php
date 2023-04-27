<?php

namespace AnzeBlaBla\Framework;

class SpecialFunction
{
    private $function;
    private $ID;

    public function __construct($function, $thisID)
    {
        $this->function = $function;
        $this->ID = $thisID;
    }

    public function call(...$args)
    {
        // use last rendered component as context
        $this->function->call(Component::$lastRendered, ...$args);
    }

    public function __toString()
    {
        return $this->generateJS();
    }

    private function generateJS()
    {
        return "callSpecialFunction('{$this->ID}')";
    }
}