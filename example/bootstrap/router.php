<?php

// Initialize router
$router = new \Neuron\Router ();

$router->get ('/', '\Example\Controllers\HomeController@main');
$router->get ('/templates', '\Example\Controllers\HomeController@templates');

return $router;