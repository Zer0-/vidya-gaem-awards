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

    function testRouteIteration(){
        $h = new StdClass;
        $root = new Route($h, 'root');
        $r1 = new Route($h, 'r1');
        $r2 = new Route($h, 'r2');
        $r3 = new Route($h, 'r3');
        $r4 = new Route($h, 'r4');
        $r5 = new Route($h, 'r5');
        $routemap = $root->add([
            ['first', $r1->add([
                ['this-one', $r2->add([
                    ['second', $r3->add([
                        ['deepest', $r4]
                    ])]
                ])],
                ['ending', $r1]
            ])],
            ['unused', $r5]
        ]);
        $list = [];
        foreach ($routemap as $value){
            array_push($list, $value);
        }
        $testlist = [
            [['first'], $r1],
            [['first', 'this-one'], $r2],
            [['first', 'this-one', 'second'], $r3],
            [['first', 'this-one', 'second', 'deepest'], $r4],
            [['first', 'ending'], $r1],
            [['unused'], $r5]
        ];
        foreach ($testlist as $key => $validvalue){
            list($validpath, $validroute) = $validvalue;
            list($path, $route) = $list[$key];
            $this->assertEquals($path, $validpath);
            $this->assertEquals($route->name, $validroute->name);
        }
    }
}
