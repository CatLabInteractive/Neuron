<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 28/04/14
 * Time: 16:26
 */

namespace Neuron\Filter;


class Token {
	public $type, $value, $argc = 0;

	public function __construct($type, $value)
	{
		$this->type  = $type;
		$this->value = $value;
	}
} 