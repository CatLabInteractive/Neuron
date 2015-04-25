<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 20/02/15
 * Time: 15:10
 */

namespace Neuron\Collections;


class ErrorCollection
	extends Collection {

	public function getData ()
	{
		$out = array ();

		foreach ($this as $v)
			$out[] = (string) $v;

		return $out;
	}

}