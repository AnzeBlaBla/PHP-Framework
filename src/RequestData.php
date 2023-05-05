<?php

namespace AnzeBlaBla\Framework;

class RequestData
{
    public array $get;
    public array $post;
    public ?array $json = null;
    public array $data = [];

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;

        // if json type
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {
            $json = file_get_contents('php://input');
            $this->json = json_decode($json, true);
        }

        if ($this->json) {
            $this->data = array_merge($this->get, $this->post, $this->json);
        } else {
            $this->data = array_merge($this->get, $this->post);
        }
    }

}