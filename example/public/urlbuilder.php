<?php

$app = include ('../bootstrap/start.php');

echo \Neuron\URLBuilder::getAbsoluteURL ('test', array ('foo' => 'bar'));