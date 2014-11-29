<?php

$loader = require_once '../../vendor/autoload.php';

// Autoload the example app
$loader->add ('Example\\', __DIR__ . '/../app/');

// Start the app
$app = new \Neuron\Application ();

// Load the router
$app->setRouter (include ('router.php'));

// Set the template folder
\Neuron\Core\Template::addTemplatePath (__DIR__ . '/../templates/');

// Return app
return $app;