<?php

namespace AnzeBlaBla\Framework;
use Closure;
class SpecialFunction
{
    private Closure $function;
    private string $ID;

    public function __construct(Closure $function, string $thisID)
    {
        $this->function = $function;
        $this->ID = $thisID;
    }

    /**
     * Call the function
     * @param mixed ...$args
     */
    public function call(...$args)
    {
        // use last rendered component as context
        $this->function->call(Component::$lastRendered, ...$args);
    }

    public function __toString()
    {
        return $this->generateJS();
    }

    /**
     * JS to call the function from the client
     * @return string
     */
    private function generateJS()
    {
        return "callSpecialFunction('{$this->ID}')";
    }
}