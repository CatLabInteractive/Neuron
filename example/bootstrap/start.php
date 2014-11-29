<?php

error_reporting (E_ALL);

$loader = require_once '../../vendor/autoload.php';

// Autoload the example app
$loader->add ('Example\\', __DIR__ . '/../app/');

// Start the app
$app = \Neuron\Application::getInstance ();

// Load the router
$app->setRouter (include ('router.php'));

// Set config folder
\Neuron\Config::folder (__DIR__ . '/../config/');

// Optionally, set an environment
\Neuron\Config::environment ('development');

// Set the template folder
\Neuron\Core\Template::addPath (__DIR__ . '/../templates/');

// Return app
return $app;