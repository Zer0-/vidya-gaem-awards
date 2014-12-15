<?php

/*
 * The Route class is responsible for getting the appropriate function
 * to handle the Request object when Route.getView is called.
 * 
 * It is also used in constructing routemaps: nested strucutres that
 * can represent the url layout of the website.
 *
 * For example suppose you have two objects, HandlerRoot and HandlerTwo,
 * that can handle requests. And you want the url structure of your
 * website to be as follows (read '->' as "handled by"):
 *
 *      /             -> HandlerRoot
 *      /two/sublevel -> HandlerTwo
 *
 * Then you create a routemap like so:
 *
 *      $routemap = (new Route(HandlerRoot)).add([
 *          ['two', (new Route).add([
 *              ['sublevel', new Route(HandlerTwo)]
 *          ])]
 *      ]);
 *
 * A router can then take a Request and the routemap and get the right Route.
 *
 */
class Route implements Iterator{
    //attributes for iteration
    private $iter_count;
    private $iter_local_count;
    private $iter_local_count_stack;
    private $iter_current_routemap;
    private $iter_routemap_stack;
    private $iter_path;

    function __construct(
        $handler=Null,
        $name=Null,
        $handles_subtree=false,
        array $permissions=Null,
        array $routemap=Null        //Usually not manually set
    ){
        $this->handler = $handler;
        $this->name = $name;
        $this->permissions = $permissions ? $permissions : [];
        $this->handles_subtree = $handles_subtree;
        $this->routemap = $routemap ? $routemap : [];
    }

    /*
     * Sets an array like [[<pathpart>, <Route instance>], ...]
     * to this route's routemap attribute.
     *
     * NOTE: Below the term "function" is used but "function name" is meant,
     * which confusingly is also a string. A limitation of php is that we
     * cannot have an array of function references, so we simply give the name.
     *
     * A pathpart may be a string or a function. In the case of a function
     * that function must take a string pathpart as the input and either
     * raise an exception (indicating that the Route instance or anything
     * deeper down this branch of the routemap does *not* handle the url) or
     * return a value - a cleaned, parsed version of the pathpart.
     *
     */
    function add($routemap){
        return new Route(
            $this->handler,
            $this->name,
            $this->handles_subtree,
            $this->permissions,
            $routemap
        );
    }

    function __toString(){
        $info = [];
        if ($this->name)
            array_push($info, 'name: ' . $this->name);
        if ($this->handler)
            array_push($info, 'handler: ' . get_class($this->handler));
        if ($this->routemap){
            $s = 'contains ' . count($this->routemap) . ' routes';
            array_push($info, $s);
        }
        if ($info)
            return '<Route ' . implode(', ', $info) . '>';
        else
            return '<Route>';
    }

    /*
     * Iterator methods
     */
    function rewind(){
        $this->iter_count = 0;
        $this->iter_local_count = 0;
        $this->iter_local_count_stack = [];
        $this->iter_current_routemap = $this->routemap;
        $this->iter_routemap_stack = [];
        $this->iter_path = [];
    }

    function valid(){
        return array_key_exists($this->iter_local_count, $this->iter_current_routemap);
    }

    function current(){
        list($pathpart, $route) = $this->iter_current_routemap[$this->iter_local_count];
        return [array_merge($this->iter_path, [$pathpart]), $route];
    }

    function key(){
        return $this->iter_count;
    }

    function next(){
        $this->iter_count ++;
        list($pathpart, $route) = $this->iter_current_routemap[$this->iter_local_count];
        $routemap = $route->routemap;
        if ($routemap){
            array_push($this->iter_path, $pathpart);
            array_push($this->iter_routemap_stack, $this->iter_current_routemap);
            array_push($this->iter_local_count_stack, $this->iter_local_count);
            $this->iter_current_routemap = $routemap;
            $this->iter_local_count = 0;
            return;
        }
        $this->iter_local_count ++;
        while (!$this->valid() and ($this->iter_routemap_stack)){
            $this->iter_current_routemap = array_pop($this->iter_routemap_stack);
            $this->iter_local_count = array_pop($this->iter_local_count_stack);
            array_pop($this->iter_path);
            $this->iter_local_count ++;
        }
    }
}
