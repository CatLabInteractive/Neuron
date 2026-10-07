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
				if (!empty ($v)) {
					header ($k . ': ' . $v);
				}
				else {
					header ($k);
				}
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
				// A data structure shown in an HTML response is a dump of
				// values, not markup.
				echo htmlspecialchars (print_r ($response->getData (), true), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			}

			else
			{
				echo $response->getData ();
			}
		}
	}
	
} 