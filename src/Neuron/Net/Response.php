<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 18:36
 */

namespace Neuron\Net;


use Neuron\Models\User;
use Neuron\Net\Outputs\HTML;
use Neuron\Net\Outputs\JSON;
use Neuron\Net\Outputs\Output;
use Neuron\Net\Outputs\PrintR;
use Neuron\Net\Outputs\Table;

class Response
	extends Entity {

	/**
	 * @param $json
	 * @return Response
	 */
	public static function fromJSON ($json)
	{
		$model = new self ();

		$data = json_decode ($json, true);
		$model->setFromData ($data);

		if (isset ($data['output']))
		{
			switch ($data['output'])
			{
				case 'PrintR':
					$model->setOutput (new PrintR ());
					break;

				case 'Table':
					$model->setOutput (new Table ());
					break;

				case 'HTML':
					$model->setOutput (new HTML ());
					break;

				case 'JSON':
					$model->setOutput (new JSON ());
					break;
			}
		}

		return $model;
	}

	/**
	 * Show the data in a html table.
	 * @param $data
	 * @return Response
	 */
	public static function table ($data)
	{
		$in = new self ();
		$in->setData ($data);
		$in->setOutput (new Table ());
		return $in;
	}

	/**
	 * Show the data in print_r form, html.
	 * @param $data
	 * @return Response
	 */
	public static function print_r ($data)
	{
		$in = new self ();
		$in->setData ($data);
		$in->setOutput (new PrintR());
		return $in;
	}

	public static function json ($data)
	{
		$in = new self ();
		$in->setData ($data);
		$in->setOutput (new JSON ());
		return $in;
	}

	public static function error ($data)
	{
		$in = new self ();
		$in->setData ($data);
		$in->setOutput (new HTML ());
		return $in;
	}

	/**
	 * @param $name
	 * @param $data
	 * @return Response
	 */
	public static function template ($name, $data = array ())
	{
		$in = new self ();

		$template = new \Neuron\Core\Template ();

		foreach ($data as $k => $v)
		{
			$template->set ($k, $v);
		}

		$in->setBody ($template->parse ($name));
		$in->setOutput (new HTML ());

		return $in;
	}

	private $output;

	/**
	 * Create a redirect response.
	 * @param $url
	 * @return Response
	 */
	public static function redirect ($url)
	{
		$response = new self ();
		$response->setRedirect ($url);

		return $response;
	}

	/**
	 * Set a response to be a redirect.
	 * @param $url
	 * @return $this
	 */
	public function setRedirect ($url)
	{
		$this->setHeader ('Location', $url);
		$this->setStatus (302);
		$this->setData (array ('message' => 'Redirecting to ' . $url));

		return $this;
	}

	public function getJSONData ()
	{
		$data = parent::getJSONData ();

		$outputname = get_class ($this->getOutput ());
		$outputname = explode ('\\', $outputname);
		$outputname = last ($outputname);

		$data['output'] = $outputname;
		return $data;
	}

	public function setOutput (Output $output)
	{
		$this->output = $output;
	}

	public function isOutputSet ()
	{
		return isset ($this->output);
	}

	/**
	 * @return Output
	 */
	public function getOutput ()
	{
		if (!isset ($this->output))
		{
			$this->output = new HTML ();
		}
		return $this->output;
	}

	/**
	 * Send the output to stdout.
	 */
	public function output ()
	{
		$this->getOutput ()->output ($this);
	}
} 