<?php

//this is used by rint
function pretty_print_var($v){
    $raw_vals = ['string', 'boolean', 'double', 'integer'];
    $t = gettype($v);
    if ($t == 'integer' and $v == 0)
        echo "0";
    else if ($t == 'NULL')
        echo "NULL";
    else if ($t == 'boolean'){
        if ($v)
            echo "true";
        else
            echo "false";
    }
    else if (method_exists($v, '__toString'))
        echo $v;
    else if (!in_array(gettype($v), $raw_vals))
        echo var_dump($v);
    else
        echo $v;
}

//a print function named 'rint' that tries to behave a bit more like Python's print
function rint($var=600000, $var2=600000, $var3=600000){
    if (gettype($var) == 'integer' and $var == 600000){
        echo "\n";
        return;
    }
    pretty_print_var($var);
    if (gettype($var2) != 'integer' or $var2 != 600000){
        echo ' ';
        pretty_print_var($var2);
    }
    if (gettype($var3) != 'integer' or $var3 != 600000){
        echo ' ';
        pretty_print_var($var3);
    }
    echo "\n";
}

function _match_part($part, array $routemap){
    foreach ($routemap as $tuple){
        list($key, $route) = $tuple;
        if (is_callable($key)){
            try{
                return [$key($part), $route];
            }
            catch (UnexpectedValueException $e){
                return Null;
            }
        }
        else if ($key == $part){
            return $tuple;
        }
    }
}

/*
 * Traverses a routemap, adding a [pathpart, Route] pair to a list
 * whenever a pathpart in the given path is found in the routemap.
 *
 * See also the testMatchPath method in routerTest.php for an example.
 */
function match_path(array $path, Route $route){
    $matched = [['/', $route]];
    foreach ($path as $pathpart){
        $match = _match_part($pathpart, $route->routemap);
        if (is_null($match)){
            if ($route->handles_subtree == false){
                return 404;
            }
        }
        else{
            list($pathpart, $route) = $match;
        }
        array_push($matched, [$pathpart, $route]);
    }
    return $matched;
}

/*
 * RouteApi finds the Route that handles the given path. It can also give the
 * variable path parts of the url and reverse lookup the path given a
 * route name.
 * 
 * The path array that RouteApi takes as the first argument is simply
 * the exploded url path:
 *
 *      '/my/desired/path' -> ['my', 'desired', 'path']
 *
 * See route.php for a description of routemap.
 */
class RouteApi{
    function __construct(array $path, Route $routemap){
        $this->routemap = $routemap;
        $this->_matched_routes = match_path($path, $routemap);
    }

    function get_path(){
        $path = [];
        foreach ($this->_matched_routes as $match){
            array_push($path, $match[0]);
        }
        return array_slice($path, 1);
    }
}
