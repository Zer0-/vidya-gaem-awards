<?php

$SRC = realpath(dirname(__FILE__) . '/../../src');

require_once $SRC . '/routing/route.php';
require_once $SRC . '/routing/router.php';

function use_int($var){
    if (is_numeric($var))
        return (integer) ($var);
    else
        throw new UnexpectedValueException("Cannot convert value to an integer");
}

function endval($x){
    if ($x == "endvar")
        return $x;
    else
        throw new UnexpectedValueException("Not endvar");
}

class TestRouteApi extends PHPUnit_Framework_TestCase{
    function setUp(){
        $this->root = new Route(new StdClass, 'root');
        $this->r1 = new Route(new StdClass, 'r1');
        $this->r2 = new Route(new StdClass, 'r2', true);
        $this->r3 = new Route(new StdClass, 'r3');
        $this->r4 = new Route(new StdClass, 'r4', true);
        $this->r5 = new Route(new StdClass, 'r5');
        $this->routemap = $this->root->add([
            ['first', $this->r1->add([
                ['use_int', $this->r2->add([
                    ['second', $this->r3->add([
                        ['use_int', $this->r4]
                    ])]
                ])],
                ['endval', $this->r1]
            ])],
            ['not_used', $this->r5]
        ]);
    }

    /*
     * Note that the use of use_int as the pathpart causes the output
     * matched pathpart to be an integer, since it runs the given pathpart
     * through use_int. This feature is useful for validating path parts,
     * allowing one entry in a routemap to match many urls.
     */
    function testMatchPath(){
        $path = ['first', '1', 'one', 'two', 'second', '2', 'three', 'four'];
        $matched = match_path($path, $this->routemap);
        $shouldmatch = [
            ['/',      $this->root],
            ['first',  $this->r1],
            [1,        $this->r2],
            ['one',    $this->r2],
            ['two',    $this->r2],
            ['second', $this->r3],
            [2,        $this->r4],
            ['three',  $this->r4],
            ['four',   $this->r4]
        ];
        foreach ($matched as $key => $m){
            $this->assertEquals($m[0], $shouldmatch[$key][0]);
            $this->assertEquals($m[1]->name, $shouldmatch[$key][1]->name);
        }
    }

    function testConvertInt(){
        $s = "1234";
        $this->assertEquals(use_int($s), 1234);
        //aparantly strings can be callable
        $v = 'use_int';
        $this->assertTrue(is_callable($v));
        $this->assertEquals($v('1234'), 1234);
        $this->setExpectedException('UnexpectedValueException');
        use_int("123a4");
    }

    function testRouteApiPath(){
        $path = ['first', '1', 'one', 'two', 'second', '2', 'three', 'four'];
        $api = new RouteApi($path, $this->routemap);
        $wanted = ['first', 1, 'one', 'two', 'second', 2, 'three', 'four'];
        $this->assertEquals($api->get_path(), $wanted);
    }

    function testRouteApiEmptyVars(){
        $path = ['first'];
        $api = new RouteApi($path, $this->routemap);
        $wanted = [];
        $this->assertEquals($api->get_vars(), $wanted);
    }

    function testRouteApiVars(){
        $path = ['first', '1', 'one', 'two', 'second', '2', 'three', 'four'];
        $api = new RouteApi($path, $this->routemap);
        $wanted = [1, 'one', 'two', 2, 'three', 'four'];
        $this->assertEquals($api->get_vars(), $wanted);
    }

    function testRouteRoute(){
        $path = ['first', '1', 'one', 'two', 'second', '2', 'three', 'four'];
        $api = new RouteApi($path, $this->routemap);
        $this->assertEquals($api->get_route()->name, 'r4');
    }

    function testRouteMatching(){
        $path_route_pairs = [
            ['/first', $this->r1],
            ['/first/1000', $this->r2],
            ['/first/3/', $this->r2],
            ['/first/3/a/b/c/d/e', $this->r2],
            ['/first/3/a/b/second', $this->r3],
            ['/first/3/a/b/second/3', $this->r4],
            ['/first/3/a/b/second/44/c/d/e', $this->r4],
        ];
        foreach ($path_route_pairs as $pair){
            list($urlpath, $correct_route) = $pair;
            $path = create_path($urlpath);
            $api = new RouteApi($path, $this->routemap);
            $this->assertEquals($api->get_route($path)->name, $correct_route->name);
        }
    }

    function testEmptyRelative(){
        $path = ['first'];
        $api = new RouteApi($path, $this->routemap);
        $wants = [];
        $this->assertEquals($api->get_relative(), $wants);
    }

    function testRelative(){
        $path = ['first', '1', 'one', 'two', 'second', '2', 'three', 'four'];
        $api = new RouteApi($path, $this->routemap);
        $wants = [['one', 'two'], ['three', 'four']];
        $this->assertEquals($api->get_relative(), $wants);
    }
}
