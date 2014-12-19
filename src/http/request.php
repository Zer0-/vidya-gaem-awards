<?php

require_once dirname(__FILE__) . '/../routing/router.php';

class Request{
    function __construct($env, $routemap){
        $this->env = $env;
        $this->route = new RouteApi(create_path($this->get_path()), $routemap);
    }

    function get_path(){
        return $this->env['REQUEST_URI'];
    }
}
