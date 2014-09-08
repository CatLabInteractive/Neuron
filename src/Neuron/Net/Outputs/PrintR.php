<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/08/14
 * Time: 17:21
 */

namespace Neuron\Net\Outputs;


use Neuron\Net\Response;

class PrintR extends HTML {

	public function __construct ()
	{
		
	}

	public function outputContent (Response $response)
	{
		if (!is_string ($response->getData ()))
		{
			echo '<pre>' . print_r ($response->getData ()) . '<pre>';
		}

		else
		{
			echo $response->getData ();
		}
	}
	
} 