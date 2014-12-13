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

    function testRoutePathparts(){
        $route = (new Route)->add([
            ['one', new Route],
            ['two', (new Route)->add([
                ['sublevel', new Route]
            ])]
        ]);
        $parts = ['one', 'two'];
        $this->assertEquals($route->get_pathparts(), $parts);
    }
}
