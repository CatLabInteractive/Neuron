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

		$template->set ('first', Config::get ('app.example.first'));
		$template->set ('second', Config::get ('app.example.second'));
		$template->set ('third', Config::get ('app.example.third'));

		$template->set ('counts', Config::get ('app.example'));

		return Response::template ($template);
	}

	public function templates ()
	{
		Template::addPath ('lowpriority', '', -5);
		Template::addPath ('regularpriority', '', 0);
		Template::addPath ('highpriority', '', 5);

		return Response::table (Template::getPaths());
	}
}