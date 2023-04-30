<?php

namespace AnzeBlaBla\Framework;

class Portal
{

    private ?string $content = null;
    private string $defaultContent = '';

    private bool $contentSet = false;
    private bool $allowSet = true;
    private bool $exceptionOnSet = false;
    
    function __construct(private string $portalKey)
    {
        //$this->content = $defaultContent;
    }

    public function getDefaultContent(): string
    {
        return $this->defaultContent;
    }

    public function setDefaultContent(string $defaultContent): Portal
    {
        $this->defaultContent = $defaultContent;

        if ($this->content === null)
            $this->content = $defaultContent;
        return $this;
    }

    /* public function setExceptionOnSet(bool $exceptionOnSet = true): Portal
    {
        $this->exceptionOnSet = $exceptionOnSet;

        return $this;
    } */

    public function getContent(): string
    {
        return $this->content ?? ''; // TODO: maybe shouldn't always be a string
    }

    public function set(string $content, bool $allowOtherSet = false): Portal
    {
        /* echo json_encode([
            'portalKey' => $this->portalKey,
            'content' => $content,
            'allowOtherSet' => $allowOtherSet,
            'contentSet' => $this->contentSet,
            'allowSet' => $this->allowSet,
            'exceptionOnSet' => $this->exceptionOnSet,
        ]); */

        if ($this->contentSet && !$this->allowSet)
        {
            // If wanted, throw an exception, otherwise just do nothing
            if ($this->exceptionOnSet)
                throw new \Exception("Portal {$this->portalKey} already set");
            else
                return $this;
        }

        $this->content = $content;

        $this->contentSet = true;

        if (!$allowOtherSet)
            $this->allowSet = false;

        return $this;
    }

    public function append(string $content): Portal
    {
        if (is_array($this->content))
            $this->content[] = $content;
        else
            $this->content .= $content;
        
        $this->contentSet = true;

        return $this;
    }

    public function reset(): Portal
    {
        $this->content = $this->defaultContent;

        $this->contentSet = false;

        return $this;
    }

    public function getPlaceholder(): string
    {
        return "<!-- {PORTAL:{$this->portalKey}} -->";
    }


    public function __toString(): string
    {
        return $this->getPlaceholder();
    }
}
