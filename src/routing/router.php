<?php

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
    if ($urlpath == '/')
        return [];
    return array_slice(explode('/', $urlpath), 1);
}

function path_to_urlpart($path){
    return '/' . implode('/', $path);
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
 * the exploded url path (use the create_path function above):
 *
 *      '/my/desired/path' -> ['my', 'desired', 'path']
 *
 * An empty array would be the root.
 *
 * See route.php for a description of routemap.
 */
class RouteApi{
    function __construct(array $path, Route $routemap){
        $this->routemap = $routemap;
        $this->matched_routes = match_path($path, $routemap);
    }

    /*
     * returns the cleaned path as an array of pathparts
     */
    function get_path(){
        return array_slice(_all_first($this->matched_routes), 1);
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
        foreach (array_slice($this->matched_routes, 1) as $match){
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
        return end($this->matched_routes)[1];
    }

    /*
     * Returns arrays of pathparts that belong to the same Route object
     * with handles_subtree set.
     *
     * For example, given the routemap:
     *
     *      $routemap = (new Route($handler, 'root', true)).add([
     *          ['second', new Route($handler, 'second', true)]
     *      ]);
     *
     * and a url path like ['one', 'two', 'second', '2', 'three']
     * get_relative would return:
     *
     *    [['one', 'two'], ['2', 'three']]
     *
     * Note that because 'second' matches the key for the Route called 'second'
     * a new array of pathparts is started. So it's possible to have multiple
     * routes with handles_subtree set in the same branch of the routemap tree.
     */
    function get_relative(){
        $routemap = $this->routemap;
        $relative_paths = [];
        $current = [];
        foreach ($this->matched_routes as $m){
            list($part, $route) = $m;
            if ($route == $routemap and $route->handles_subtree)
                array_push($current, $part);
            else{
                $routemap = $route;
                if($current){
                    array_push($relative_paths, $current);
                    $current = [];
                }
            }
        }
        if ($current)
            array_push($relative_paths, $current);
        return $relative_paths;
    }

    /*
     * Given a path, potentially full of validation functions, replaces
     * the functions with items from arg_iter to create an actual path.
     */
    private function fill_path($path, $arg_iter){
        $filled_path = [];
        foreach ($path as $pathpart){
            if (!is_callable($pathpart))
                array_push($filled_path, $pathpart);
            else{
                $arg = $arg_iter->current();
                $arg_iter->next();
                try{
                    array_push($filled_path, $pathpart($arg));
                }
                catch (UnexpectedValueException $e){
                    return Null;
                }
            }
        }
        return $filled_path;
    }

    /*
     * Find the path of a route given its name. If the path you are looking
     * for contains variables, pass them as the 'path_args' parameter.
     */
    function find($routename, $path_args=[]){
        $match = function($path) use ($path_args){
            $count = 0;
            foreach ($path as $pathpart){
                if (is_callable($pathpart))
                    $count ++;
            }
            return $count == count($path_args);
        };
        foreach($this->routemap as $r){
            list($path, $existing_route) = $r;
            if ($routename == $existing_route->name){
                $arg_iter = new ArrayIterator($path_args);
                if ($existing_route->handles_subtree){
                    $filled_path = $this->fill_path($path, $arg_iter);
                    if (is_null($fill_path))
                        continue;
                    return path_to_urlpart(array_merge($filled_path, iterator_to_array($arg_iter))); 
                }
                else if ($match($path)){
                    $filled_path = $this->fill_path($path, $arg_iter);
                    if (is_null($filled_path))
                        continue;
                    return path_to_urlpart($filled_path); 
                }
            }
        }
    }
}
