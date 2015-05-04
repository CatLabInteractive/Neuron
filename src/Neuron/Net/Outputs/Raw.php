<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/08/14
 * Time: 17:21
 */

namespace Neuron\Net\Outputs;


use Neuron\Net\Response;

class Raw extends HTML {

	public function outputContent (Response $response)
	{
		if ($response->getBody ())
		{
			echo $response->getBody ();
		}
		else
		{
			echo $response->getData ();
		}
	}
	
} 