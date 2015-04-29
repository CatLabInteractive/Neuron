<?php

$loader = require_once 'vendor/autoload.php';

// Start the app
$app = \Neuron\Application::getInstance ();

// Set config folder
\Neuron\Config::folder (__DIR__ . '/../example/config/');