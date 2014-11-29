<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/11/14
 * Time: 13:06
 */

namespace Example\Controllers;

use Neuron\Core\Template;

class HomeController {

	public function main ()
	{
		$template = new Template ('example.phpt');

		return \Neuron\Net\Response::template ($template);
	}

}