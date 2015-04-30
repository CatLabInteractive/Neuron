<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 18:36
 */

namespace Neuron\Net;


use Neuron\Core\Template;
use Neuron\Models\User;
use Neuron\Net\Outputs\HTML;
use Neuron\Net\Outputs\JSON;
use Neuron\Net\Outputs\Output;
use Neuron\Net\Outputs\PrintR;
use Neuron\Net\Outputs\Table;
use Neuron\Net\Outputs\XML;

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

	public static function fromRaw ($body, $headers) {

		$out = new self ();
		$out->setBody ($body);
		$out->setHeaders ($headers);
		$out->parseData ();

		return $out;

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

	/**
	 * @param $data
	 * @param string $root XML Root element name
	 * @param string $version Root element version number
	 * @param array $parameters Array of root element attributes
	 * @param string $itemName Generic item name.
	 * @return Response
	 */
	public static function xml ($data, $root = 'root', $version = '1.0', array $parameters = array (), $itemName = 'item')
	{
		$in = new self ();
		$in->setData ($data);
		$in->setOutput (new XML ($root, $version, $parameters, $itemName));
		return $in;
	}

	/**
	 * @param string $message
	 * @param int $statuscode
	 * @return Response
	 */
	public static function error ($message, $statuscode = 500)
	{
		$template = new Template ('error.phpt');
		$template->set ('message', $message);
		$template->set ('status', $statuscode);

		$response = self::template ($template);
		$response->setStatus ($statuscode);


		return $response;
	}

	/**
	 * @param Template|string $template
	 * @param array $data
	 * @return Response
	 */
	public static function template ($template, $data = array ())
	{
		$in = new self ();

		if (! ($template instanceof Template))
		{
			$template = new Template ($template);

		}

		foreach ($data as $k => $v)
		{
			$template->set ($k, $v);
		}

		$in->setTemplate ($template);

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

	private function setTemplate (Template $template)
	{
		$this->setBody ($template->parse ());
		$this->setOutput (new HTML ());

		return $this;
	}

	public function setOutput (Output $output)
	{
		$this->output = $output;
		return $this;
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