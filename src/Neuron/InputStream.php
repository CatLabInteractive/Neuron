<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 12/03/14
 * Time: 13:57
 */

namespace Neuron;

class InputStream {

	private $input;

	public static function getInstance ()
	{
		static $in;
		if (!isset ($in))
		{
			$in = new self ();
		}

		return $in;
	}

	private function __construct ()
	{
		$this->setInput (file_get_contents ('php://input'));
	}

	public static function getInput ()
	{
		return self::getInstance ()->input;
	}

	public function setInput ($input)
	{
		$this->input = $input;
	}
} 