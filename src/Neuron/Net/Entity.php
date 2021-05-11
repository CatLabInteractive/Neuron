<?php

namespace Neuron\Net;

use Neuron\Application;
use Neuron\Config;
use Neuron\Exceptions\DataNotSet;
use Neuron\Exceptions\InvalidParameter;

/**
 * Class Entity
 * @package Neuron\Net
 */
abstract class Entity
{

	const CHECK_SIGNATURE = false;

	/**
	 * @var Session $session
	 */
	private $session;

	private $body;
	private $path;
	private $post;
	private $headers;
	private $data;
	private $cookies;

	/** @var string $error */
	private $error;

	/** @var integer $status */
	private $status;

	private $application;

	const STATUS_OKAY = 200;
	const STATUS_NOTFOUND = 404;
	const STATUS_INVALID_INPUT = 400;

	const STATUS_UNAUTHORIZED = 403;

	/**
	 * Serialize & deserialize requests
	 * @param $data
	 * @return \Neuron\Net\Request
	 * @throws \Neuron\Exceptions\InvalidParameter
	 */
	public function setFromData($data)
	{
		// Check signature
		$model = $this;

		$chk = self::CHECK_SIGNATURE;

		if ($chk && !isset ($data['signature'])) {
			throw new InvalidParameter ("All decoded requests must have a signature.");
		}

		if ($chk && $data['signature'] != self::calculateSignature($data)) {
			throw new InvalidParameter ("Leave now, and Never come Back! *gollem, gollem* (Decoded request signature mismatch).");
		}

		// The body. If data is found, body is not used.
		if (isset ($data['data']) && !empty ($data['data'])) {
			$model->setData($data['data']);
		} else if (isset ($data['body'])) {
			$model->setBody($data['body']);
		}

		if (isset ($data['headers'])) {
			$model->setHeaders($data['headers']);
		}

		if (isset ($data['cookies'])) {
			$model->setCookies($data['cookies']);
		}

		if (isset ($data['post'])) {
			$model->setPost($data['post']);
		}

		if (isset ($data['status'])) {
			$model->setStatus($data['status']);
		}

		return $model;
	}

	/**
	 * Parse data based on content type.
	 */
	protected function parseData()
	{
		$contentType = $this->getHeader('content-type');
		if ($contentType) {
			switch (strtolower($contentType)) {
				case 'application/json':
				case 'text/json':

					$data = json_decode($this->getBody(), true);

					if (!$data) {
						$this->setError('JSON decode error: ' . json_last_error_msg());
					} else {
						$this->setData($data);
					}

					break;

				default:
					$this->setData($_POST);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getJSONData()
	{
		$data = array();

		if ($this->getData()) {
			$plaindata = $this->getData();
			if (!empty ($plaindata)) {
				$data['data'] = $this->getData();
			}
		}

		$data['body'] = $this->getBody();

		$data['headers'] = $this->getHeaders();
		$data['cookies'] = $this->getCookies();
		$data['post'] = $this->getPost();
		$data['status'] = $this->getStatus();

		return $data;
	}

	/**
	 * @param array $data
	 * @return string
	 */
	private static function calculateSignature(array $data)
	{
		$txt = self::calculateBaseString($data);
		return md5($txt);
	}

	private static function calculateBaseString(array $data)
	{
		unset ($data['signature']);

		$txt = '\(^-^)/ !Stupid Rainbow Tables! \(^-^)/ ';
		foreach ($data as $k => $v) {
			$txt .= $k . ":" . json_encode($v) . "|";
		}

		$txt .= Config::get('app.secret');

		return $txt;
	}

	/**
	 * @return string json
	 */
	public function toJSON()
	{
		$data = $this->getJSONData();
		$data['random'] = mt_rand();
		$data['time'] = gmdate('c');
		$data['signature'] = $this->calculateSignature($data);
		return json_encode($data);
	}

	/**
	 * @return \Neuron\Net\Session
	 * @throws DataNotSet
	 */
	public function getSession()
	{
		if (!isset ($this->session)) {
			// First check the router
			if ($this instanceof Response) {
				$router = Application::getInstance()->getRouter();
				if ($router) {
					$this->session = $router->getRequest()->getSession();
				}
			} else {
				throw new DataNotSet ("No session is set in the request.");
			}
		}
		return $this->session;
	}

	/**
	 * @param Session $session
	 * @return $this
	 */
	public function setSession(Session $session)
	{
		$this->session = $session;
		return $this;
	}

	/**
	 * @param $body
	 * @return $this
	 */
	public function setBody($body)
	{
		$this->body = $body;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param $id
	 * @return $this
	 */
	public function setApplication($id)
	{
		$this->setSession('application', $id);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @param $path
	 * @return $this
	 */
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param array $post
	 * @return $this
	 */
	public function setPost($post)
	{
		$this->post = $post;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getPost()
	{
		return $this->post;
	}

	/**
	 * @param mixed $data
	 * @return $this
	 */
	public function setData($data)
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Check if request has data
	 */
	public function hasData()
	{
		if (!isset ($this->data)) {
			if (!isset ($this->error))
				$this->setError('No input data set');

			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return $this
	 */
	public function setHeader($name, $value = null)
	{
		if (!isset ($this->headers)) {
			$this->headers = array();
		}
		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * @param array $headers
	 * @return $this
	 */
	public function setHeaders($headers)
	{
		$this->headers = $headers;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * @param $name
	 * @return string|null
	 */
	public function getHeader($name)
	{
		$name = $this->getSafeHeaderName($name);
		foreach ($this->getHeaders() as $k => $v) {
			if ($this->getSafeHeaderName($k) === $name) {
				return $v;
			}
		}
		return null;
	}

	/**
	 * @param $name
	 * @return string
	 */
	protected function getSafeHeaderName($name)
	{
		$name = str_replace('-', '_', $name);
		$name = str_replace('_', ' ', $name);
		$name = strtolower($name);
		$name = ucwords($name);
		$name = str_replace(' ', '-', $name);

		return $name;
	}

	public function setCookies($cookies)
	{
		$this->cookies = $cookies;
		return $this;
	}

	public function getCookies()
	{
		return $this->cookies;
	}

	public function setStatus($status)
	{
		$this->status = $status;
		return $this;
	}

	public function isStatusSet()
	{
		return isset ($this->status);
	}

	public function getStatus()
	{
		if (isset ($this->status)) {
			return $this->status;
		}
		return 200;
	}

	/**
	 * @param $error
	 * @return $this
	 */
	public function setError($error)
	{
		$this->error = $error;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Return an error response.
	 * @return Response
	 */
	public function getErrorResponse()
	{
		return Response::error($this->getError(), self::STATUS_INVALID_INPUT);
	}
} 
