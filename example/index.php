<?php

error_reporting (E_ALL);

require_once '../vendor/autoload.php';

require_once 'ExampleModule/Module.php';
require_once 'ExampleModule/Controllers/TestController.php';

// Register template directory
\Neuron\Core\Template::addTemplatePath (dirname (__FILE__) . '/templates/');

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

// Regular view, no module
$router->get ('/view', function () {
    return \Neuron\Net\Response::template ('example.phpt');
});

// Set module.
$router->module ('/module', new Module ());

$router->run ();