<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 30/11/14
 * Time: 18:49
 */

namespace Neuron\Collections;

/**
 * Class TokenizedCollection
 *
 * Adds
 *
 * @package Neuron\Collections
 */
class TokenizedCollection
	extends Collection {

	private $tokens = array ();

	/**
	 * Generate a unique token.
	 * @param $object
	 * @return string
	 */
	protected function generateToken ($object)
	{
		$token = get_class ($object);

		if ($pos = strrpos ($token, "\\")) {
			$token = strtolower (substr ($token, $pos + 1));
		}

		if (isset ($this->tokens[$token]))
		{
			$i = 1;
			$tmp = $token . $i;
			while (isset ($this->tokens[$tmp]))
			{
				$i ++;
			}
			$token = $tmp;
		}

		$this->tokens[$token] = true;
		return $token;
	}

}