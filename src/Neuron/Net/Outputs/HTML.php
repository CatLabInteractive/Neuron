<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 8/08/14
 * Time: 17:21
 */

namespace Neuron\Net\Outputs;


use Neuron\Net\Response;

class HTML implements Output {

	public function __construct ()
	{
		
	}
	
	public function output (Response $response)
	{
		if ($response->getStatus ())
		{
			http_response_code ($response->getStatus ());
		}

		if ($response->getHeaders ())
		{
			foreach ($response->getHeaders () as $k => $v)
			{
				header ($k . ': ' . $v);
			}
		}

		if ($response->getCookies ())
		{
			foreach ($response->getCookies () as $k => $v)
			{
				setcookie ($k, $v);
			}
		}

		$this->outputContent ($response);
	}
	
	public function outputContent (Response $response)
	{
		if ($response->getBody ())
		{
			echo $response->getBody ();
		}
		else
		{
			if (!is_string ($response->getData ()))
			{
				print_r ($response->getData ());
			}

			else
			{
				echo $response->getData ();
			}
		}
	}
	
} 