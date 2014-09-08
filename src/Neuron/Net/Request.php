<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 18:36
 */

namespace Neuron\Net;


use Neuron\Exceptions\InvalidParameter;
use Neuron\InputStream;
use Neuron\MapperFactory;
use Neuron\Models\User;

class Request
	extends Entity {

	/**
	 * @return Request
	 */
	public static function fromInput ()
	{
		global $module;

		$model = new self ();

		$model->setMethod (isset($_SERVER['REQUEST_METHOD'])? $_SERVER['REQUEST_METHOD']: null);
		if (isset ($module))
		{
			$model->setPath ($module);

			$input = explode ('/', $module);
			$model->setSegments ($input);
		}

		$model->setBody (InputStream::getInput ());
		$model->setHeaders (getallheaders ());
		$model->setParameters ($_GET);
		$model->setCookies ($_COOKIE);
		$model->setPost ($_POST);
		$model->setEnvironment ($_SERVER);
		$model->setStatus (http_response_code ());

		return $model;
	}

	/**
	 * @param $json
	 * @return Request
	 */
	public static function fromJSON ($json)
	{
		$model = new self ();

		$data = json_decode ($json, true);
		$model->setFromData ($data);

		if (isset ($data['method']))
		{
			$model->setMethod ($data['method']);
		}

		if (isset ($data['parameters']))
		{
			$model->setParameters ($data['parameters']);
		}

		if (isset ($data['segments']))
		{
			$model->setSegments ($data['segments']);
		}
		
		if (isset ($data['environment']))
		{
			$model->setEnvironment ($data['environment']);
		}

		return $model;
	}

	private $method = 'GET';
	private $url;
	private $parameters;
	private $input;
	private $environment;

	/**
	 * @param $method
	 */
	public function setMethod ($method)
	{
		$this->method = $method;
	}

	/**
	 * @return string
	 */
	public function getMethod ()
	{
		return $this->method;
	}

	/**
	 * @param $url
	 */
	public function setUrl ($url)
	{
		$this->url = $url;
	}

	/**
	 * @return mixed
	 */
	public function getUrl ()
	{
		return $this->url;
	}

	/**
	 * @param array $parameters
	 */
	public function setParameters (array $parameters)
	{
		$this->parameters = $parameters;
	}

	/**
	 * @param $string
	 */
	public function setQueryString ($string)
	{
		$this->parameters = parse_url ($string);
	}

	/**
	 * @return mixed
	 */
	public function getParameters ()
	{
		return $this->parameters;
	}

	/**
	 * @param $input
	 */
	public function setSegments ($input)
	{
		$this->input = $input;
	}

	/**
	 * @return null
	 */
	public function getSegments ()
	{
		return $this->getSegment ();
	}

	/**
	 * @param null $input
	 * @return null
	 */
	public function getSegment ($input = null)
	{
		if (isset ($input))
		{
			if (isset ($this->input[$input]))
			{
				return $this->input[$input];
			}
			return null;
		}
		return $this->input;
	}

	/**
	 * @return array
	 */
	public function getJSONData ()
	{
		$data = parent::getJSONData ();

		$data['url'] = $this->getUrl ();
		$data['method'] = $this->getMethod ();
		$data['parameters'] = $this->getParameters ();
		$data['segments'] = $this->getSegments ();
		$data['environment'] = $this->getEnvironment ();

		return $data;
	}

	public function setEnvironment ($data)
	{
		$this->environment = $data;
	}

	public function getEnvironment ()
	{
		return $this->environment;
	}
} 