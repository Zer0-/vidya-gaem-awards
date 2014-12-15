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

/*
 * Given a multi-dimensional array, return an array of just the first values
 * of the nested arrays.
 */
function _all_first($array){
    $values = [];
    foreach ($array as $value){
        array_push($values, $value[0]);
    }
    return $values;
}

function create_path($urlpath){
    return array_slice(explode('/', $urlpath), 1);
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

    /*
     * returns the cleaned path as an array of pathparts
     */
    function get_path(){
        return array_slice(_all_first($this->_matched_routes), 1);
    }

    /*
     * Returns an array of pathparts (in original order) that were matched
     * by a function in the routemap rather than hardcoded. Also returns
     * pathparts matched by a route having handles_subtree set.
     */
    function get_vars(){
        $routemap = $this->routemap->routemap;
        $parts = _all_first($routemap);
        $vars = [];
        foreach (array_slice($this->_matched_routes, 1) as $match){
            list($part, $route) = $match;
            if (in_array($part, $parts)){
                $routemap = $route->routemap;
                $parts = _all_first($routemap);
            }
            else{
                array_push($vars, $part);
                if ($route->routemap != $routemap)
                    $routemap = $route->routemap;
                    $parts = _all_first($routemap);
            }
        }
        return $vars;
    }

    /*
     * Get the route that handles our path
     */
    function get_route(){
        return end($this->_matched_routes)[1];
    }
}
