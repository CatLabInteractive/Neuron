<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 12/03/14
 * Time: 13:57
 */

namespace Neuron\Net;

use Neuron\Core\Tools;

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

    /**
     * Check if the inputstream contains a valid utf8 string.
     * @return bool
     */
    public static function isValidUTF8 ()
    {
        return Tools::isValidUTF8 (self::getInstance ()->getInput ());
    }
} 