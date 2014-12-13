<?php

$SRC = realpath(dirname(__FILE__) . '/../../src');

require_once $SRC.'/routing/route.php';

class TestRoute extends PHPUnit_Framework_TestCase{
    function testTrivialCreation(){
        $route = new Route;
        $this->assertEquals($route->handler, NULL);
        $this->assertEquals($route->name, NULL);
        $this->assertEquals($route->permissions, []);
        $this->assertEquals($route->handles_subtree, false);
    }

    function testTreeCreation(){
        $route = (new Route)->add([
            ['one', new Route],
            ['two', (new Route)->add([
                ['sublevel', new Route]
            ])]
        ]);
        $this->assertEquals(count($route->routemap), 2);
    }

    function testRouteToString(){
        $route = new Route;
        $this->assertEquals((string) $route, '<Route>');
        $foo = new stdClass;
        $route = (new Route($foo, 'myroute'))->add([
            ['one', new Route],
            ['two', (new Route)->add([
                ['sublevel', new Route]
            ])]
        ]);
        $s = '<Route name: myroute, handler: stdClass, contains 2 routes>';
        $this->assertEquals((string) $route, $s);
    }
}
