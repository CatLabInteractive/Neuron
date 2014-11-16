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
		$model->setMethod (self::getMethodFromInput ());

		if (isset ($module))
		{
			$model->setPath ($module);

			$input = explode ('/', $module);
			$model->setSegments ($input);
		}

		$model->setBody (InputStream::getInput ());
		$model->setHeaders (self::getHeadersFromInput ());
		$model->setParameters ($_GET);
		$model->setCookies ($_COOKIE);
		$model->setPost ($_POST);
		$model->setEnvironment ($_SERVER);
		$model->setStatus (http_response_code ());

		return $model;
	}

	/**
	 * Get all request headers
	 * @return array The request headers
	 */
	private static function getHeadersFromInput ()
	{
		// getallheaders available, use that
		if (function_exists('getallheaders')) return getallheaders();

		// getallheaders not available: manually extract 'm
		$headers = array();
		foreach ($_SERVER as $name => $value) {
			if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
				$headers[str_replace(array(' ', 'Http'), array('-', 'HTTP'), ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}

	/**
	 * Get the request method used, taking overrides into account
	 * @return string The Request method to handle
	 */
	private static function getMethodFromInput ()
	{
		// Take the method as found in $_SERVER
		$method = $_SERVER['REQUEST_METHOD'];

		// If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
		// @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
		if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
			ob_start();
			$method = 'GET';
		}

		// If it's a POST request, check for a method override header
		else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$headers = self::getHeadersFromInput ();
			if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], array('PUT', 'DELETE', 'PATCH'))) {
				$method = $headers['X-HTTP-Method-Override'];
			}
		}

		return $method;
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