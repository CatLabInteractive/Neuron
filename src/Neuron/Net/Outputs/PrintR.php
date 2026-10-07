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

	/**
	 * @param mixed $text
	 * @return string
	 */
	private static function escape ($text)
	{
		return htmlspecialchars ((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	public function outputContent (Response $response)
	{
		if (!is_string ($response->getData ()))
		{
			echo '<pre>' . self::escape (print_r ($response->getData (), true)) . '</pre>';
		}

		else
		{
			echo '<pre>' . self::escape ($response->getData ()) . '</pre>';
		}
	}
	
} 