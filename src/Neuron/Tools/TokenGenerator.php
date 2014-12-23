<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 23/12/14
 * Time: 22:46
 */

namespace Neuron\Tools;


class TokenGenerator {

	public static function getToken ($length)
	{
		$range = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		$out = '';
		for ($i = 0; $i < $length; $i ++)
		{
			$out .= substr ($range, mt_rand (0, strlen ($range)), 1);
		}
		return $out;
	}

}