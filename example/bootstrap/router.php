<?php

// Initialize router
$router = new \Neuron\Router ();

$router->get ('/', '\Example\Controllers\HomeController@main');
$router->get ('/templates', '\Example\Controllers\HomeController@templates');

$router->get ('/test/{something?}', function ($a) {
	return \Neuron\Net\Response::json ($a);
});

return $router;