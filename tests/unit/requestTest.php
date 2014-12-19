<?php

$SRC = realpath(dirname(__FILE__) . '/../../src');
require_once $SRC.'/http/request.php';

function create_request($path, $routemap){
    $env = [
        'REQUEST_URI' => $path
    ];
    return new Request($env, $routemap);
}

class TestRequest extends PHPUnit_Framework_TestCase{
    function testRootRequest(){
        $r = create_request('/', new Route(new StdClass, 'test'));
        $this->assertEquals($r->route->get_route()->name, 'test');
    }
}
