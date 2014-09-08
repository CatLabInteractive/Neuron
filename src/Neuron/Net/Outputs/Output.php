<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/08/14
 * Time: 17:22
 */

namespace Neuron\Net\Outputs;


use Neuron\Net\Response;

interface Output {

	public function output (Response $response);
	
} 