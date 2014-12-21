<?php

require_once dirname(__FILE__) . '/../routing/router.php';

/*
 * Represents an incomming http request. Essentially an api wrapper around
 * $_SERVER but will eventually let us cleanly handle cookies, headers and
 * uploaded files.
 * 
 * The 'env' argument will usually be $_SERVER
 */
class Request{
    function __construct($env, $routemap){
        $this->env = $env;
        $this->route = new RouteApi(create_path($this->get_path()), $routemap);
    }

    function get_path(){
        return $this->env['REQUEST_URI'];
    }

    /*
     * GET, HEAD, POST, PUT, DELETE, etc....
     */
    function get_method(){
        return $this->env['REQUEST_METHOD'];
    }
}
