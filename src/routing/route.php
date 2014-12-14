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
    private $iter_current_iterator;
    private $iter_stack;
    private $iter_path;

    function __construct(
        $handler=Null,
        $name=Null,
        array $permissions=Null,
        $handles_subtree=false
    ){
        $this->handler = $handler;
        $this->name = $name;
        $this->permissions = $permissions ? $permissions : [];
        $this->handles_subtree = $handles_subtree;
        $this->routemap = [];
    }

    /*
     * Sets an array like [[<pathpart>, <Route instance>], ...]
     * to this route's routemap attribute.
     *
     * A pathpart may be a string or a function. In the case of a function
     * that function must take a string pathpart as the input and either
     * raise an exception (indicating that the Route instance or anything
     * deeper down this branch of the routemap does *not* handle the url) or
     * return a value - a cleaned, parsed version of the pathpart.
     *
     */
    function add($routemap){
        $this->routemap = $routemap;
        return $this;
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
        $this->iter_current_iterator = new ArrayIterator($this->routemap);
        $this->iter_stack = [];
        $this->iter_path = [];
        echo "REWIND\n";
        echo "count: ", $this->iter_count, "\n";
        echo "iter_path: ", var_dump($this->iter_path), "\n";
    }

    function valid(){
        return $this->iter_current_iterator->valid();
    }

    function current(){
        list($pathpart, $route) = $this->iter_current_iterator->current();
        return [$this->iter_path + $pathpart, $route];
    }

    function key(){
        return $this->iter_count;
    }

    function next(){
        $this->iter_count ++;
        if ($this->iter_current_iterator->valid()){
            array_push($this->iter_stack, $this->iter_current_iterator);
            list($pathpart, $route) = $this->iter_current_iterator->current();
            array_push($this->iter_path, $pathpart);
            $next_iter = $route;
            $next_iter->rewind();
            $this->iter_current_iterator = $next_iter;
            return;
        }
        $this->iter_current_iterator->next();
        if (!$this->iter_current_iterator->valid()){
            $this->iter_current_iterator = array_pop($this->iter_stack);
        }
    }
}
