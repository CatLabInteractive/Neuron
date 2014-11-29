<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 29/11/14
 * Time: 13:06
 */

namespace Example\Controllers;

use Neuron\Config;
use Neuron\Core\Template;
use Neuron\Net\Response;

class HomeController {

	public function main ()
	{
		$template = new Template ('example.phpt');

		$template->set ('title', Config::get ('app.name'));

		return Response::template ($template);
	}

}