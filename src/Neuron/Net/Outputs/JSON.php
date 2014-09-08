<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/08/14
 * Time: 17:21
 */

namespace Neuron\Net\Outputs;


use Neuron\Net\Response;

class JSON extends HTML {

	public function __construct ()
	{
		
	}
	
	public function outputContent (Response $response)
	{
		header ('Content-type: application/json');

		$out = json_encode ($response->getData ());
		if ($out)
		{
			echo $out;
		}

		else if ($error = json_last_error())
		{
			http_response_code (500);
			echo 'json_encode failed with error code ' . $error;
		}
	}
	
} 