<?php
class TestTest extends PHPUnit_Framework_TestCase{
    public function testHelloWorld(){
        $string = "Hello";
        $this->assertEquals($string, "Hello");
    }
}
