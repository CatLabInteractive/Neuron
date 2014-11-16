<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 16/11/14
 * Time: 16:55
 */

class TestController {

    public function doSomething ()
    {
        $response = \Neuron\Net\Response::json (array ('doSomething data' => 'yep'));
        return $response;
    }

    public function foo ()
    {
        $response = \Neuron\Net\Response::json (array ('sdf' => 'yep'));
        return $response;
    }

    public function template ()
    {
        $data = array (
            'name' => 'Test variable ' . mt_rand (0, 5000)
        );

        $response = \Neuron\Net\Response::template ('example/moduleexample.phpt', $data);
        return $response;
    }

    public function home ()
    {
        $data = array (
            'name' => 'Test variable ' . mt_rand (0, 5000)
        );

        $response = \Neuron\Net\Response::template ('example/moduleexample.phpt', $data);
        return $response;
    }
}