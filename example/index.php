<?php

error_reporting (E_ALL);

require_once '../vendor/autoload.php';

$router = new \Neuron\Router ();

$router->get ('/response', function () {

    return \Neuron\Net\Response::json (array ('test'));

});

$router->get ('/string', function () {

    return 'String';

});

$router->get ('/void', function () {

    echo 'void';

});

$router->run ();