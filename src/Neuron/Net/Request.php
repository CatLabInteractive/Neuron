<?php
/**
 * Created by PhpStorm.
 * User: daedeloth
 * Date: 21/04/14
 * Time: 18:36
 */

namespace Neuron\Net;


use Exception;
use Neuron\Core\Tools;
use Neuron\Exceptions\InvalidParameter;
use Neuron\Interfaces\Models\User;
use Neuron\Models\Router\Route;

class Request
	extends Entity {

	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';
	const METHOD_PATCH = 'PATCH';
	const METHOD_PUT = 'PUT';
	const METHOD_OPTIONS = 'OPTIONS';

	/** @var User $user */
	private $user;

	/** @var callable[] $usercallback */
	private $usercallback = array ();

	private $usercallbackcalled = false;

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
		$model->setUrl (self::getCurrentUri ());

		$model->parseData ();

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
		$headers = '';
		foreach ($_SERVER as $name => $value)
		{
			if (substr($name, 0, 5) == 'HTTP_')
			{
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}

			else if (strtolower (substr ($name, 0, 7)) == 'content')
			{
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))))] = $value;
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
	 * Define the current relative URI
	 * @return string
	 */
	private static function getCurrentUri ()
	{
		// Get the current Request URI and remove rewrite basepath from it (= allows one to run the router in a subfolder)
		$basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
		$uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));

		// Don't take query params into account on the URL
		if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));

		// Remove trailing slash + enforce a slash at the start
		$uri = '/' . trim($uri, '/');

		return $uri;

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

	/**
	 * Return a request based on a simple url.
	 * @param string $url
	 * @param string $stripHost
	 * @return Request
	 */
	public static function fromURL ($url, $stripHost) {

		$data = parse_url ($url);

		$parameters = array ();

		if (!empty ($data['query']))
			parse_str ($data['query'], $parameters);

		$request = new self ();

		if ($stripHost) {
			$request->setUrl ($data['path']);
		}

		else {
			$request->setUrl (
				$data['scheme'] . '://' .
				$data['host'] . (isset ($data['port']) ? ':' . $data['port'] : '') .
				$data['path']
			);
		}

		$request->setParameters ($parameters);

		return $request;

	}

	private $method = 'GET';
	private $url;
	private $parameters;
	private $input;
	private $environment;

	/**
	 * @param $method
	 * @return $this
	 */
	public function setMethod ($method)
	{
		$this->method = $method;
		return $this;
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
	 * @return $this
	 */
	public function setUrl ($url)
	{
		$this->url = $url;
		return $this;
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
	 * @return $this
	 */
	public function setParameters (array $parameters)
	{
		$this->parameters = $parameters;
		return $this;
	}

	/**
	 * @param $string
	 * @return $this
	 */
	public function setQueryString ($string)
	{
		$this->parameters = parse_url ($string);
		return $this;
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
	 * @return $this
	 */
	public function setSegments ($input)
	{
		$this->input = $input;
		return $this;
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
		return $this;
	}

	public function getEnvironment ()
	{
		return $this->environment;
	}

	/**
	 * @return bool
	 */
	public function isPost ()
	{
		return $this->getMethod () === self::METHOD_POST;
	}

	/**
	 * @return bool
	 */
	public function isGet ()
	{
		return $this->getMethod () === self::METHOD_GET;
	}

	/**
	 * @return bool
	 */
	public function isPut ()
	{
		return $this->getMethod () === self::METHOD_PUT;
	}

	/**
	 * @return bool
	 */
	public function isPatch ()
	{
		return $this->getMethod () === self::METHOD_PATCH;
	}

	/**
	 * @return bool
	 */
	public function isOptions ()
	{
		return $this->getMethod () === self::METHOD_OPTIONS;
	}

	/**
	 * Similar to fetching a value from $_REQUEST
	 * @param $field
	 * @param string $type
	 * @param mixed $default
	 * @param string $separator
	 * @return mixed|null
	 */
	public function input ($field, $type = 'string', $default = null, $separator = ',')
	{
		// Check for array type
		$array = false;
		if (substr ($type, -2) === '[]') {
			$arrtype = substr ($type, 0, -2);
			$type = 'string';
			$array = true;
		}

		// Check post
		$value = Tools::getInput ($this->getPost (), $field, $type);
		if ($value === null)
		{
			// Check get
			$value = Tools::getInput ($this->getParameters (), $field, $type);
		}

		if ($value === null)
		{
			return $default;
		}

		// Check if array?
		if ($array) {
			$values = explode ($separator, $value);

			$value = array ();
			foreach ($values as $v) {
				if (Tools::checkInput ($v, $arrtype)) {
					$value[] = $v;
				}
			}
		}

		return $value;
	}

	/**
	 * Helper method to make it easier for authentication modules
	 * @param User $user
	 * @return $this
	 */
	public function setUser (User $user)
	{
		$this->user = $user;
		return $this;
	}

	/**
	 * To allow lazy loading of the user object, set a callback here.
	 * Method will be called with request as parameter and only once a script.
	 * @param callable $callback
	 * @throws InvalidParameter
	 * @return $this
	 */
	public function setUserCallback (callable $callback)
	{
		//$this->usercallback = $callback;

		if (count ($this->usercallback) > 0)
			throw new InvalidParameter ("A usercallback was already set. Use addUserCallback to add multiple callbacks");

		$this->addUserCallback ('default', $callback);

		return $this;
	}

	/**
	 * To allow for multiple authentication methods, extra user callbacks can be set.
	 * Each callback must have a unique name. This name can be used in getUser to force
	 * @param $name
	 * @param callable $callback
	 * @throws InvalidParameter
	 * @return $this
	 */
	public function addUserCallback ($name, callable $callback)
	{
		if (isset ($this->usercallback[$name]))
			throw new InvalidParameter ("A usercallback with name " . $name . " is already set. Each callback must have a unique name.");

		$this->usercallback[$name] = $callback;

		return $this;
	}

	/**
	 * @param string $callbackName To force a specific callback
	 * @return \Neuron\Interfaces\Models\User
	 */
	public function getUser ($callbackName = null)
	{
		if (!isset ($this->user) && !$this->usercallbackcalled)
		{
			$this->usercallbackcalled = true;

			if (isset ($this->usercallback[$callbackName]))
			{
				$this->user = call_user_func ($this->usercallback[$callbackName], $this);
			}

			else {
				// Loop trough all callbacks until we find one that returns something
				$user = null;
				foreach ($this->usercallback as $cb)
				{
					$user = call_user_func ($cb, $this);
					if ($user)
						break;
				}
				$this->user = $user;
			}
		}

		return $this->user;
	}

	/**
	 * Evaluate a route and return the parameters.
	 * @param Route $route
	 * @return array|bool
	 */
	public function parseRoute (Route $route) {

		if (preg_match_all('#^' . $route->getRoute () . '$#', $this->getUrl (), $matches, PREG_OFFSET_CAPTURE)) {

			// Rework matches to only contain the matches, not the orig string
			$matches = array_slice($matches, 1);

			// Extract the matched URL parameters (and only the parameters)
			$params = array_map(function($match, $index) use ($matches) {

				// We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
				if (isset($matches[$index+1]) && isset($matches[$index+1][0]) && is_array($matches[$index+1][0])) {
					return trim(substr($match[0][0], 0, $matches[$index+1][0][1] - $match[0][1]), '/');
				}

				// We have no following paramete: return the whole lot
				else {
					return (isset($match[0][0]) ? trim($match[0][0], '/') : null);
				}

			}, $matches, array_keys($matches));

			if (empty ($params)) {
				return true;
			}
			else {
				return $params;
			}
		}

		return false;
	}
} 