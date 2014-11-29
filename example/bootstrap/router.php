<?php

// Initialize router
$router = new \Neuron\Router ();

$router->get ('/', '\Example\Controllers\HomeController@main');

return $router;